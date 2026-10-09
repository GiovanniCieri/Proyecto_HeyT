"""Confirm a created order using only the isolated X-Vittles-Location header."""

import json
import uuid
from run_blackbox import credential, observations, request


def main():
    status, auth = request("auth", "POST", "/oauth/token", {
        "client_id": credential("client_id"), "client_secret": credential("client_secret")})
    if status != 200:
        raise RuntimeError("Auth failed")
    token = auth["access_token"]
    body = {"location_id": "loc_1001", "client_ref": "audit-confirm-" + uuid.uuid4().hex,
            "customer": {"name": "Synthetic Audit", "phone": "+10000000000"},
            "items": [{"item_id": "itm_88", "quantity": 2}]}
    headers = {"X-Vittles-Location": "loc_1001"}
    status, created = request("create_with_only_required_header", "POST", "/v1/orders",
                              body, token=token, headers=headers)
    if status == 201 and isinstance(created, dict) and created.get("id"):
        request("read_created", "GET", "/v1/orders/" + str(created["id"]), token=token)
        request("repeat_same_ref_and_header", "POST", "/v1/orders",
                body, token=token, headers=headers)
    print(json.dumps({"observations": observations}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
