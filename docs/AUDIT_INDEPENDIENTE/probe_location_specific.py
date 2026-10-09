"""Check a second target location and an unavailable item by HTTP."""

import json
import uuid
from run_blackbox import credential, observations, request


def main():
    status, auth = request("auth", "POST", "/oauth/token", {
        "client_id": credential("client_id"), "client_secret": credential("client_secret")})
    if status != 200:
        raise RuntimeError("Auth failed")
    token = auth["access_token"]
    for case, location_id in [("order_airport", "loc_1003"),
                              ("order_unavailable_beachside", "loc_1005"),
                              ("order_disabled_warehouse", "loc_1004")]:
        body = {"location_id": location_id,
                "client_ref": "audit-target-" + uuid.uuid4().hex,
                "customer": {"name": "Synthetic Audit", "phone": "+10000000000"},
                "items": [{"item_id": "itm_88", "quantity": 2}]}
        status, _ = request(case, "POST", "/v1/orders", body, token=token,
                            headers={"X-Vittles-Location": location_id})
        if status == 429:
            break
    print(json.dumps({"observations": observations}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
