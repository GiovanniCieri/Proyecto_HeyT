"""Repeatable black-box probe of the locally running Vittles mock.

Reads the published demo credentials from API_DOCS.md only in memory. Never
prints credentials, bearer tokens, Authorization headers, or customer details.
Run: python docs/AUDIT_INDEPENDIENTE/run_blackbox.py > evidence.json
"""

import json
import re
import sys
import uuid
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen
from urllib.parse import quote


ROOT = Path(__file__).resolve().parents[2]
DOC = (ROOT / "docs/Docs_API/vittles/API_DOCS.md").read_text(encoding="utf-8")
BASE = "http://127.0.0.1:8422"
SECRET_KEYS = {"access_token", "client_secret", "authorization", "phone", "customer"}


def credential(name):
    match = re.search(r'"' + re.escape(name) + r'"\s*:\s*"([^"]+)"', DOC)
    if not match:
        raise RuntimeError("Missing demo credential in API_DOCS.md")
    return match.group(1)


def scrub(value):
    if isinstance(value, dict):
        return {key: ("[REDACTED]" if key.lower() in SECRET_KEYS else scrub(item))
                for key, item in value.items()}
    if isinstance(value, list):
        return [scrub(item) for item in value]
    return value


observations = []


def request(case, method, path, body=None, token=None, headers=None):
    payload = None if body is None else json.dumps(body).encode("utf-8")
    request_headers = {"Accept": "application/json"}
    if payload is not None:
        request_headers["Content-Type"] = "application/json"
    if token is not None:
        request_headers["Authorization"] = "Bearer " + token
    if headers:
        request_headers.update(headers)
    req = Request(BASE + path, data=payload, headers=request_headers, method=method)
    try:
        with urlopen(req, timeout=5) as response:
            status, data = response.status, response.read()
            response_headers = dict(response.headers.items())
    except HTTPError as error:
        status, data = error.code, error.read()
        response_headers = dict(error.headers.items())
    try:
        parsed = json.loads(data.decode("utf-8"))
    except (ValueError, UnicodeDecodeError):
        parsed = data.decode("utf-8", errors="replace")[:200]
    recorded_headers = {key: value for key, value in response_headers.items()
                        if key.lower() in {"content-type", "retry-after", "date"}}
    safe_request_headers = {key: value for key, value in (headers or {}).items()
                            if key.lower().startswith("x-") or key.lower() in
                            {"location", "location-id", "idempotency-key"}}
    if token is not None:
        safe_request_headers["Authorization"] = "Bearer [REDACTED]"
    observations.append({"case": case, "request": {"method": method, "path": path,
                         "headers": safe_request_headers, "body": scrub(body)}, "response": {"status": status,
                         "headers": recorded_headers, "body": scrub(parsed)}})
    return status, parsed


def main():
    client_id, client_secret = credential("client_id"), credential("client_secret")
    request("auth_bad_credentials", "POST", "/oauth/token",
            {"client_id": client_id, "client_secret": "wrong-audit-value"})
    status, auth = request("auth_valid", "POST", "/oauth/token",
                           {"client_id": client_id, "client_secret": client_secret})
    if status != 200 or not isinstance(auth, dict) or not auth.get("access_token"):
        raise RuntimeError("Authentication failed; evidence is incomplete")
    token = auth["access_token"]
    request("locations_no_token", "GET", "/v1/locations")
    request("locations_invalid_token", "GET", "/v1/locations", token="invalid-audit-token")
    status, locations = request("locations_valid", "GET", "/v1/locations", token=token)
    if status != 200:
        raise RuntimeError("Cannot enumerate locations; evidence is incomplete")
    location_list = locations.get("data", []) if isinstance(locations, dict) else []
    cursor = locations.get("next_cursor") if isinstance(locations, dict) else None
    seen_cursors = set()
    while cursor and cursor not in seen_cursors:
        seen_cursors.add(cursor)
        page_status, page = request("locations_cursor_" + str(cursor), "GET",
                                    "/v1/locations?cursor=" + quote(str(cursor)), token=token)
        if page_status != 200 or not isinstance(page, dict):
            break
        location_list.extend(page.get("data", []))
        cursor = page.get("next_cursor")
    menus = []
    for location in location_list:
        location_id = location["id"]
        status, menu = request("menu_" + location_id, "GET",
                               "/v1/locations/" + location_id + "/menu", token=token)
        menus.append((location_id, status, menu))
    request("menu_unknown_location", "GET", "/v1/locations/loc_nonexistent_audit/menu", token=token)
    if location_list:
        request("menu_no_token", "GET", "/v1/locations/" + location_list[0]["id"] + "/menu")
    request("order_missing_fields", "POST", "/v1/orders", {}, token=token)
    request("order_no_token", "POST", "/v1/orders", {})
    chosen = None
    for location_id, status, menu in menus:
        if status != 200 or not isinstance(menu, dict):
            continue
        for item in menu.get("menuItems", []):
            if item.get("available") in (True, "true", "True", 1):
                chosen = (location_id, item)
                break
        if chosen:
            break
    if not chosen:
        print(json.dumps({"observations": observations, "incomplete": "No available menu item found"},
                         ensure_ascii=False, indent=2))
        return
    location_id, item = chosen
    ref = "audit-" + uuid.uuid4().hex
    body = {"location_id": location_id, "client_ref": ref,
            "customer": {"name": "Synthetic Audit", "phone": "+10000000000"},
            "items": [{"item_id": item["id"], "quantity": 2}]}
    status, created = request("order_create", "POST", "/v1/orders", body, token=token)
    request("order_repeat_same_ref", "POST", "/v1/orders", body, token=token)
    if isinstance(created, dict) and created.get("id"):
        request("order_read_created", "GET", "/v1/orders/" + str(created["id"]), token=token)
    request("order_unknown", "GET", "/v1/orders/ord_nonexistent_audit", token=token)
    request("order_read_no_token", "GET", "/v1/orders/ord_nonexistent_audit")
    print(json.dumps({"observations": observations}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    try:
        main()
    except URLError as error:
        print("HTTP service unavailable: " + str(error), file=sys.stderr)
        sys.exit(1)
