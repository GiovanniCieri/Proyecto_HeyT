"""Measure whether the advertised 90-second token expiry is enforced."""

import json
import time
from run_blackbox import credential, observations, request


def main():
    status, auth = request("auth", "POST", "/oauth/token", {
        "client_id": credential("client_id"), "client_secret": credential("client_secret")})
    if status != 200:
        raise RuntimeError("Auth failed")
    token = auth["access_token"]
    initial_status, _ = request("locations_immediate", "GET", "/v1/locations", token=token)
    if initial_status != 200:
        raise RuntimeError("Control request failed")
    start = time.monotonic()
    time.sleep(98)
    elapsed = round(time.monotonic() - start, 3)
    request("locations_after_wait", "GET", "/v1/locations", token=token)
    print(json.dumps({"wait_seconds": elapsed, "observations": observations},
                     ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
