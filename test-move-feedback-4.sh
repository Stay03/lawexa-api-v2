#!/bin/bash

API_URL="https://rest.lawexa.com/api"
API_TOKEN="575|36Rna5CptgTH81uQDpPcACUi6fQkB3v9kc2Nl2oNfc164342"

echo "=========================================="
echo "Testing Move Feedback #4 to Issues"
echo "=========================================="
echo ""

# Move feedback ID 4 to issues
echo "=== Moving Feedback ID 4 to Issues with Custom Parameters ==="
echo ""
curl -X POST "${API_URL}/admin/feedback/4/move-to-issues" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "feature_request",
    "severity": "medium",
    "priority": "high",
    "status": "open",
    "area": "frontend",
    "category": "UI/UX",
    "admin_notes": "User reported multiple issues - needs investigation. Created from feedback system."
  }' | jq '.'

echo ""
echo ""
echo "=========================================="
echo "Verification: Check if Issue was Created"
echo "=========================================="
echo ""

# Get the latest issue to verify it was created
echo "=== Latest Issue in the System ==="
curl -X GET "${API_URL}/admin/issues?per_page=1&sort_by=created_at&sort_order=desc" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" | jq '.data.issues[0] | {id, title, type, severity, from_feedback, feedback_id: .feedback.id}'

echo ""
echo ""

# Verify the feedback was updated
echo "=== Feedback #4 After Moving ==="
curl -X GET "${API_URL}/admin/feedback/4" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" | jq '.data.feedback | {id, moved_to_issues, issue: .issue}'
