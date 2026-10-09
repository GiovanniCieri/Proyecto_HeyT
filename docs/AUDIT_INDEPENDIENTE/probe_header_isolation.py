"""Isolate the required order header with an empty body, avoiding order creation."""

import json
from run_blackbox import credential, observations, request


def main():
    status, auth = request("auth", "POST", "/oauth/token", {
        "client_id": credential("client_id"), "client_secret": credential("client_secret")})
    if status != 200:
        raise RuntimeError("Auth failed")
    token = auth["access_token"]
    for name in ["Location-Id", "Location", "X-Vittles-Location",
                 "X-Restaurant-Id", "X-POS-Location-Id"]:
        response_status, _ = request("header_" + name.lower(), "POST", "/v1/orders",
                                     {}, token=token, headers={name: "loc_1001"})
        if response_status == 429:
            break
    print(json.dumps({"observations": observations}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
