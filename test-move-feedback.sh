#!/bin/bash

API_URL="https://rest.lawexa.com/api"
API_TOKEN="575|36Rna5CptgTH81uQDpPcACUi6fQkB3v9kc2Nl2oNfc164342"

echo "Testing move feedback to issues endpoint..."
echo ""

# Test 1: Move feedback ID 1 to issues
echo "=== Test 1: Moving Feedback ID 1 to Issues ==="
curl -X POST "${API_URL}/admin/feedback/1/move-to-issues" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "bug",
    "severity": "high",
    "priority": "high",
    "status": "open",
    "area": "frontend",
    "admin_notes": "Issue created from feedback - testing the new implementation"
  }' | jq '.'

echo ""
echo ""

# Test 2: Check if the issue appears in the issues list
echo "=== Test 2: Checking Issues List ==="
curl -X GET "${API_URL}/admin/issues" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" | jq '.data.issues[0:3]'

echo ""
echo ""

# Test 3: Check feedback list for moved items
echo "=== Test 3: Checking Moved Feedback ==="
curl -X GET "${API_URL}/admin/feedback?moved_to_issues=true" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" | jq '.data.feedback[0:2]'
