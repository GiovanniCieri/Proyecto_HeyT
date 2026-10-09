"""Repeat the earlier documented-context hypotheses without source inspection."""

import json
import uuid
from run_blackbox import credential, observations, request


def main():
    status, auth = request("auth_valid", "POST", "/oauth/token", {
        "client_id": credential("client_id"), "client_secret": credential("client_secret")})
    if status != 200:
        raise RuntimeError("Authentication unavailable")
    token = auth["access_token"]
    body = {"location_id": "loc_1001", "client_ref": "audit-context-" + uuid.uuid4().hex,
            "customer": {"name": "Synthetic Audit", "phone": "+10000000000"},
            "items": [{"item_id": "itm_88", "quantity": 2}]}
    for case, path, candidate_body, headers in [
        ("header_x_location_context", "/v1/orders", body, {"X-Location-Context": "loc_1001"}),
        ("header_x_store_id", "/v1/orders", body, {"X-Store-Id": "loc_1001"}),
        ("query_location_id", "/v1/orders?location_id=loc_1001", body, {}),
        ("body_locationId", "/v1/orders", {**body, "locationId": "loc_1001"}, {}),
        ("body_store_id", "/v1/orders", {**body, "store_id": "loc_1001"}, {}),
        ("location_scoped_path", "/v1/locations/loc_1001/orders", body, {}),
    ]:
        result_status, _ = request(case, "POST", path, candidate_body,
                                   token=token, headers=headers)
        if result_status == 429:
            break
    request("get_unknown", "GET", "/v1/orders/ord_nonexistent_audit", token=token)
    request("get_unknown_no_token", "GET", "/v1/orders/ord_nonexistent_audit")
    print(json.dumps({"observations": observations}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
