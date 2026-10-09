"""Focused black-box probes for the order's undocumented location context.

Run after the rate-limit window has reset:
python docs/AUDIT_INDEPENDIENTE/probe_order_context.py > order_context.json
"""

import json
import uuid
from run_blackbox import credential, observations, request


def main():
    status, auth = request("auth_valid", "POST", "/oauth/token", {
        "client_id": credential("client_id"), "client_secret": credential("client_secret")})
    if status != 200:
        raise RuntimeError("Authentication unavailable")
    token = auth["access_token"]
    ref = "audit-context-" + uuid.uuid4().hex
    body = {"location_id": "loc_1001", "client_ref": ref,
            "customer": {"name": "Synthetic Audit", "phone": "+10000000000"},
            "items": [{"item_id": "itm_88", "quantity": 2}]}
    for case, path, candidate_body, headers in [
        ("body_location_context", "/v1/orders", {**body, "location_context": "loc_1001"}, {}),
        ("body_location", "/v1/orders", {**body, "location": "loc_1001"}, {}),
        ("body_context_location_id", "/v1/orders", {**body, "context": {"location_id": "loc_1001"}}, {}),
        ("body_location_object", "/v1/orders", {**body, "location": {"id": "loc_1001"}}, {}),
        ("header_x_partner_location_id", "/v1/orders", body, {"X-Partner-Location-Id": "loc_1001"}),
    ]:
        response_status, response_body = request(case, "POST", path, candidate_body,
                                                 token=token, headers=headers)
        if response_status in (201, 202) or (isinstance(response_body, dict) and response_body.get("id")):
            order_id = response_body.get("id")
            if order_id:
                request("get_created", "GET", "/v1/orders/" + str(order_id), token=token)
                request("get_created_no_token", "GET", "/v1/orders/" + str(order_id))
            request("repeat_accepted", "POST", path, candidate_body, token=token, headers=headers)
            break
    request("get_unknown", "GET", "/v1/orders/ord_nonexistent_audit", token=token)
    request("get_unknown_no_token", "GET", "/v1/orders/ord_nonexistent_audit")
    print(json.dumps({"observations": observations}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
