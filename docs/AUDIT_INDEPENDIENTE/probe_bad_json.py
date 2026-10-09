"""Check the response to syntactically malformed JSON with valid context."""

import json
from urllib.error import HTTPError
from urllib.request import Request, urlopen
from run_blackbox import BASE, credential, observations, request


def main():
    status, auth = request("auth", "POST", "/oauth/token", {
        "client_id": credential("client_id"), "client_secret": credential("client_secret")})
    if status != 200:
        raise RuntimeError("Auth failed")
    req = Request(BASE + "/v1/orders", method="POST", data=b"{",
                  headers={"Content-Type": "application/json",
                           "X-Vittles-Location": "loc_1001",
                           "Authorization": "Bearer " + auth["access_token"]})
    try:
        with urlopen(req, timeout=5) as response:
            status, raw, headers = response.status, response.read(), response.headers
    except HTTPError as error:
        status, raw, headers = error.code, error.read(), error.headers
    try:
        body = json.loads(raw.decode("utf-8"))
    except ValueError:
        body = raw.decode("utf-8", errors="replace")[:200]
    observations.append({"case": "malformed_json", "request": {
        "method": "POST", "path": "/v1/orders",
        "headers": {"Content-Type": "application/json",
                    "X-Vittles-Location": "loc_1001",
                    "Authorization": "Bearer [REDACTED]"},
        "body": "{"}, "response": {"status": status,
        "headers": {key: value for key, value in headers.items()
                    if key.lower() in {"date", "content-type", "retry-after"}},
        "body": body}})
    print(json.dumps({"observations": observations}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
