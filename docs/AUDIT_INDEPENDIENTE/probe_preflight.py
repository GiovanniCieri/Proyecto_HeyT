"""Exercise query-before-create and a later execution's recovery by client_ref."""

import json
import uuid
from urllib.parse import quote
from run_blackbox import credential, observations, request
from probe_recovery import safe_lookup


def auth(case):
    status, body = request(case, "POST", "/oauth/token", {
        "client_id": credential("client_id"), "client_secret": credential("client_secret")})
    if status != 200:
        raise RuntimeError("Auth failed")
    return body["access_token"]


def main():
    ref = "audit-preflight-" + uuid.uuid4().hex
    path = "/v1/orders?client_ref=" + quote(ref)
    token = auth("auth_first_execution")
    if safe_lookup("preflight_no_order", path, token, ref) != 200:
        raise RuntimeError("Preflight lookup failed")
    if observations[-1]["response"]["summary"].get("data_length") != 0:
        raise RuntimeError("Preflight unexpectedly found an order; no POST sent")
    body = {"location_id": "loc_1001", "client_ref": ref,
            "customer": {"name": "Synthetic Audit", "phone": "+10000000000"},
            "items": [{"item_id": "itm_88", "quantity": 2}]}
    status, created = request("create_once", "POST", "/v1/orders", body,
                              token=token, headers={"X-Vittles-Location": "loc_1001"})
    if status != 201 or not isinstance(created, dict) or not created.get("id"):
        raise RuntimeError("Creation failed")
    safe_lookup("lookup_after_creation", path, token, ref)
    if created["id"] not in observations[-1]["response"]["summary"]["target_order_ids"]:
        raise RuntimeError("Created order not found by client_ref")
    second_token = auth("auth_second_execution")
    safe_lookup("second_execution_preflight", path, second_token, ref)
    if created["id"] not in observations[-1]["response"]["summary"]["target_order_ids"]:
        raise RuntimeError("Second execution did not find the order")
    request("second_execution_read_existing", "GET", "/v1/orders/" + created["id"],
            token=second_token)
    print(json.dumps({"observations": observations}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
