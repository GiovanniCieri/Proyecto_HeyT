"""Independent differential probes for order context; no server source inspection.

Run against a fresh rate-limit window:
python3 docs/AUDIT_INDEPENDIENTE/probe_order_matrix.py > docs/AUDIT_INDEPENDIENTE/order_matrix.json
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
    ref = "audit-matrix-" + uuid.uuid4().hex
    base = {"location_id": "loc_1001", "client_ref": ref,
            "customer": {"name": "Synthetic Audit", "phone": "+10000000000"},
            "items": [{"item_id": "itm_88", "quantity": 2}]}
    aliases = ["locationId", "location", "location_context", "store_id", "storeId",
               "restaurant_id", "restaurantId", "merchant_id", "merchantId",
               "site_id", "siteId", "outlet_id", "outletId", "venue_id", "venueId",
               "branch_id", "branchId", "business_id", "businessId",
               "context_location_id", "contextLocationId"]
    all_body = {**base, **{key: "loc_1001" for key in aliases}}
    candidates = [
        ("all_body_aliases", "/v1/orders", all_body, {}),
        ("nested_context_aliases", "/v1/orders", {**base,
            "context": {"location": "loc_1001", "location_id": "loc_1001",
                        "locationId": "loc_1001", "store_id": "loc_1001"},
            "location_context": {"id": "loc_1001", "location_id": "loc_1001"}}, {}),
        ("all_header_aliases", "/v1/orders", base, {
            "Location-Id": "loc_1001", "Location": "loc_1001",
            "X-Location-Id": "loc_1001", "X-Location": "loc_1001",
            "X-Vittles-Location": "loc_1001", "X-Vittles-Location-Id": "loc_1001",
            "X-Store-Id": "loc_1001", "X-Restaurant-Id": "loc_1001",
            "X-POS-Location-Id": "loc_1001", "X-Partner-Location-Id": "loc_1001",
            "X-Location-Context": "loc_1001"}),
        ("all_query_aliases", "/v1/orders?location_id=loc_1001&locationId=loc_1001&location=loc_1001&store_id=loc_1001", base, {}),
    ]
    for case, path, body, headers in candidates:
        result_status, result = request(case, "POST", path, body, token=token, headers=headers)
        if result_status == 429:
            break
        if isinstance(result, dict) and result.get("id"):
            request("accepted_read", "GET", "/v1/orders/" + str(result["id"]), token=token)
            request("accepted_repeat", "POST", path, body, token=token, headers=headers)
            break
    print(json.dumps({"observations": observations}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
