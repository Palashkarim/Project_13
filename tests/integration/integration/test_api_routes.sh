#!/usr/bin/env bash
# Simple curl smoke tests; require API to be up
API_URL="${API_URL:-http://localhost/api}"
curl -s -X GET "$API_URL/widgets" | jq .
