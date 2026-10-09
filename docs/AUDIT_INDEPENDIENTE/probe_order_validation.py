"""Probe boundary payloads with confirmed location context.

The server may accept a payload expected to be invalid, creating an order.
"""

import json
import uuid
from run_blackbox import credential, observations, request


def main():
    status, auth = request("auth", "POST", "/oauth/token", {
        "client_id": credential("client_id"), "client_secret": credential("client_secret")})
    if status != 200:
        raise RuntimeError("Auth failed")
    token = auth["access_token"]
    header = {"X-Vittles-Location": "loc_1001"}
    base = {"location_id": "loc_1001", "client_ref": "audit-invalid-" + uuid.uuid4().hex,
            "customer": {"name": "Synthetic Audit", "phone": "+10000000000"},
            "items": [{"item_id": "itm_88", "quantity": 2}]}
    for case, body, headers in [
        ("empty_body", {}, header),
        ("empty_items", {**base, "items": []}, header),
        ("quantity_zero", {**base, "items": [{"item_id": "itm_88", "quantity": 0}]}, header),
        ("unknown_item", {**base, "items": [{"item_id": "itm_unknown_audit", "quantity": 2}]}, header),
        ("unknown_location", {**base, "location_id": "loc_unknown_audit"}, header),
    ]:
        result_status, _ = request(case, "POST", "/v1/orders", body, token=token, headers=headers)
        if result_status == 429:
            break
    print(json.dumps({"observations": observations}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
