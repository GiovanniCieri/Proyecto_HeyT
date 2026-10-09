"""Probe later retrieval of an audit order and plausible client_ref lookups.

Responses to collection-like routes are summarized to avoid saving unrelated orders.
"""

import json
from pathlib import Path
from urllib.error import HTTPError
from urllib.parse import quote
from urllib.request import Request, urlopen
from run_blackbox import BASE, credential, observations, request


HERE = Path(__file__).resolve().parent


def summarize(value, target_ref):
    matches = []

    def visit(node):
        if isinstance(node, dict):
            if node.get("client_ref") == target_ref and node.get("id"):
                matches.append(node["id"])
            for child in node.values():
                visit(child)
        elif isinstance(node, list):
            for child in node:
                visit(child)

    visit(value)
    if isinstance(value, dict):
        summary = {"type": "object", "keys": sorted(value.keys()),
                   "error": value.get("error"), "target_order_ids": matches}
        if isinstance(value.get("data"), list):
            summary["data_length"] = len(value["data"])
        return summary
    if isinstance(value, list):
        return {"type": "array", "length": len(value), "target_order_ids": matches}
    return {"type": type(value).__name__, "target_order_ids": matches}


def safe_lookup(case, path, token, target_ref):
    req = Request(BASE + path, method="GET", headers={
        "Accept": "application/json", "Authorization": "Bearer " + token})
    try:
        with urlopen(req, timeout=5) as response:
            status, raw, headers = response.status, response.read(), response.headers
    except HTTPError as error:
        status, raw, headers = error.code, error.read(), error.headers
    try:
        parsed = json.loads(raw.decode("utf-8"))
    except ValueError:
        parsed = None
    observations.append({"case": case, "request": {"method": "GET", "path": path,
        "headers": {"Authorization": "Bearer [REDACTED]"}, "body": None},
        "response": {"status": status,
        "headers": {key: value for key, value in headers.items()
                    if key.lower() in {"date", "content-type", "retry-after"}},
        "summary": summarize(parsed, target_ref)}})
    return status


def main():
    earlier = json.loads((HERE / "confirmed_order.json").read_text(encoding="utf-8"))
    created = next(item["response"]["body"] for item in earlier["observations"]
                   if item["case"] == "create_with_only_required_header")
    order_id, target_ref = created["id"], created["client_ref"]
    status, auth = request("auth_new_execution", "POST", "/oauth/token", {
        "client_id": credential("client_id"), "client_secret": credential("client_secret")})
    if status != 200:
        raise RuntimeError("Auth failed")
    token = auth["access_token"]
    request("read_prior_order_by_id", "GET", "/v1/orders/" + quote(order_id), token=token)
    encoded = quote(target_ref)
    for case, path in [
        ("query_client_ref", "/v1/orders?client_ref=" + encoded),
        ("query_clientRef", "/v1/orders?clientRef=" + encoded),
        ("path_by_client_ref", "/v1/orders/by-client-ref/" + encoded),
        ("path_ref_as_id", "/v1/orders/" + encoded),
    ]:
        if safe_lookup(case, path, token, target_ref) == 429:
            break
    print(json.dumps({"observations": observations}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
