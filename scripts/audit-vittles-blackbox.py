"""Reproduce a contract audit using only API_DOCS.md and HTTP responses.

Run with a fresh local mock when possible. The script creates two test orders
to check the documented client_ref idempotency claim. It never reads the mock
implementation or prints credentials and bearer tokens.
"""

import json
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path


BASE = "http://127.0.0.1:8422"
DOC = Path(__file__).resolve().parents[1] / "docs/Docs_API/vittles/API_DOCS.md"


def request(method, path, *, token=None, body=None, headers=None):
    outgoing_headers = dict(headers or {})
    if token:
        outgoing_headers["Authorization"] = "Bearer " + token
    if body is not None:
        outgoing_headers["Content-Type"] = "application/json"
    data = json.dumps(body).encode() if body is not None else None
    req = urllib.request.Request(BASE + path, data=data, headers=outgoing_headers, method=method)
    try:
        response = urllib.request.urlopen(req, timeout=8)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        raw = response.read().decode("utf-8", errors="replace")
        try:
            payload = json.loads(raw)
        except json.JSONDecodeError:
            payload = {"non_json_response": raw[:120]}
        return {"http": response.status, "body": payload,
                "headers": {key: value for key, value in response.headers.items()
                            if key.lower().startswith("retry-after")}}


def main():
    text = DOC.read_text(encoding="utf-8")
    match = re.search(r'\{\s*"client_id"\s*:\s*"[^"]+"\s*,\s*"client_secret"\s*:\s*"[^"]+"\s*\}', text)
    if not match:
        raise RuntimeError("Credentials example not found in API_DOCS.md")
    credentials = json.loads(match.group())
    observations = []

    def record(name, method, path, *, token=None, body=None, headers=None):
        result = request(method, path, token=token, body=body, headers=headers)
        if result["http"] == 429:
            raise RuntimeError("Rate limit interrupted the audit at " + name +
                               "; wait for the window to reset, then rerun")
        safe = {"case": name, "method": method, "path": path, **result}
        if path == "/oauth/token" and isinstance(safe["body"], dict):
            safe["body"] = {key: ("<redacted>" if key == "access_token" else value)
                            for key, value in safe["body"].items()}
        observations.append(safe)
        print(f"{name}: HTTP {result['http']}", flush=True)
        return result

    if "--extended" in sys.argv:
        authenticated_at = time.monotonic()
        auth = record("auth_without_bearer", "POST", "/oauth/token", body=credentials)
        token = auth["body"].get("access_token")
        if not token:
            raise RuntimeError("Authentication failed; cannot continue edge audit")
        remaining = max(0, 92 - (time.monotonic() - authenticated_at))
        print(f"Waiting {remaining:.0f}s to test expired token over HTTP...", flush=True)
        time.sleep(remaining)
        record("expired_token_after_92s", "GET", "/v1/locations", token=token)
        fresh = record("auth_after_expiry", "POST", "/oauth/token", body=credentials)
        fresh_token = fresh["body"].get("access_token")
        if not fresh_token:
            raise RuntimeError("Cannot test rate limit without a fresh token")
        for attempt in range(1, 65):
            response = request("GET", "/v1/locations", token=fresh_token)
            if response["http"] == 429:
                observations.append({"case": "rate_limit", "method": "GET", "path": "/v1/locations",
                                     "attempt_after_refresh": attempt, **response})
                print(f"rate_limit: HTTP 429 on attempt {attempt}", flush=True)
                break
        else:
            observations.append({"case": "rate_limit_not_observed_within_64_requests"})
            print("rate_limit: not observed within 64 requests", flush=True)
        output = Path(__file__).resolve().parents[1] / "docs/Docs_API/vittles/BLACKBOX_AUDIT_EDGE.json"
        output.write_text(json.dumps({"method": "API_DOCS.md plus HTTP only", "base_url": BASE,
                                      "observations": observations}, ensure_ascii=False, indent=2) + "\n",
                          encoding="utf-8")
        print(f"Evidence: {output}")
        return 0

    auth = record("auth_without_bearer", "POST", "/oauth/token", body=credentials)
    token = auth["body"].get("access_token")
    if not token:
        raise RuntimeError("Authentication failed; cannot continue audit")
    record("locations_without_bearer", "GET", "/v1/locations")
    record("locations_unknown_bearer", "GET", "/v1/locations", token="invalid-audit-token")

    cursor = None
    locations = []
    seen = set()
    for page in range(1, 11):
        path = "/v1/locations" + ("?" + urllib.parse.urlencode({"cursor": cursor}) if cursor else "")
        response = record(f"locations_page_{page}", "GET", path, token=token)
        if response["http"] != 200 or not isinstance(response["body"].get("data"), list):
            raise RuntimeError("Locations response unusable")
        locations.extend(response["body"]["data"])
        cursor = response["body"].get("next_cursor")
        if not cursor:
            break
        if cursor in seen:
            raise RuntimeError("Repeated pagination cursor")
        seen.add(cursor)
    else:
        raise RuntimeError("Pagination did not end within ten pages")

    menus = {}
    for location in locations:
        location_id = location["id"]
        response = record("menu_" + location_id, "GET", f"/v1/locations/{location_id}/menu", token=token)
        if response["http"] == 200:
            menus[location_id] = response["body"]
    record("menu_unknown_location", "GET", "/v1/locations/unknown-audit/menu", token=token)
    record("order_unknown_id", "GET", "/v1/orders/unknown-audit", token=token)

    location_id = "loc_1001" if "loc_1001" in menus else next(iter(menus))
    items = menus[location_id].get("menuItems", [])
    if not items:
        raise RuntimeError("No observed menu item available for POST audit")
    item_id = next((item["id"] for item in items if item.get("available")), None)
    if not item_id:
        raise RuntimeError("No available item observed")
    unique_ref = "blackbox-audit-" + str(time.time_ns())
    payload = {"location_id": location_id, "client_ref": unique_ref,
               "items": [{"item_id": item_id, "quantity": 2}]}
    record("post_without_location_header", "POST", "/v1/orders", token=token, body=payload)
    headers = {"X-Vittles-Location": location_id}
    record("post_empty_items", "POST", "/v1/orders", token=token,
           body={**payload, "items": []}, headers=headers)
    first = record("post_first", "POST", "/v1/orders", token=token, body=payload, headers=headers)
    second = record("post_same_client_ref", "POST", "/v1/orders", token=token, body=payload, headers=headers)
    record("lookup_by_client_ref", "GET", "/v1/orders?" + urllib.parse.urlencode({"client_ref": unique_ref}), token=token)
    if first["body"].get("id"):
        record("read_created_order", "GET", "/v1/orders/" + urllib.parse.quote(first["body"]["id"]), token=token)

    output = Path(__file__).resolve().parents[1] / "docs/Docs_API/vittles/BLACKBOX_AUDIT_CORE.json"
    output.write_text(json.dumps({"method": "API_DOCS.md plus HTTP only", "base_url": BASE,
                                  "observations": observations}, ensure_ascii=False, indent=2) + "\n",
                      encoding="utf-8")
    print(f"Evidence: {output}")
    if first["body"].get("id") and second["body"].get("id") and first["body"]["id"] != second["body"]["id"]:
        print("Confirmed: same client_ref created different order IDs")
    return 0


if __name__ == "__main__":
    sys.exit(main())
