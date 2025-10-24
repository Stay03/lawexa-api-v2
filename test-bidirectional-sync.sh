#!/bin/bash

API_URL="https://rest.lawexa.com/api"
API_TOKEN="576|1AlVXZMSkV0ZQjtIFTqaPp1mFWgwYHA7bfhEqj4bbf6726aa"

echo "=========================================="
echo "Testing Bidirectional Status Sync"
echo "=========================================="
echo ""

# First, check the current state of issue #104 and its linked feedback #4
echo "=== Step 1: Current State of Issue #104 and Feedback #4 ==="
echo ""
echo "Issue #104 Status:"
curl -X GET "${API_URL}/admin/issues/104" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" | jq '{
    id: .data.issue.id,
    status: .data.issue.status,
    resolved_by: .data.issue.resolved_by,
    resolved_at: .data.issue.resolved_at,
    feedback_id: .data.issue.feedback.id
  }'

echo ""
echo "Feedback #4 Status:"
curl -X GET "${API_URL}/admin/feedback/4" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" | jq '{
    id: .data.feedback.id,
    status: .data.feedback.status,
    resolved_by: .data.feedback.resolved_by,
    resolved_at: .data.feedback.resolved_at,
    issue_id: .data.feedback.issue.id
  }'

echo ""
echo ""
echo "=========================================="
echo "Test 1: Issue → Feedback Sync"
echo "=========================================="
echo ""

# Update issue #104 to "in_progress" - should sync feedback to "under_review"
echo "=== Updating Issue #104 to 'in_progress' ==="
curl -X PUT "${API_URL}/admin/issues/104" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"status": "in_progress"}' | jq '{
    issue_status: .data.issue.status,
    issue_resolved_by: .data.issue.resolved_by
  }'

echo ""
echo "=== Checking if Feedback #4 synced to 'under_review' ==="
curl -X GET "${API_URL}/admin/feedback/4" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" | jq '{
    id: .data.feedback.id,
    status: .data.feedback.status,
    status_name: .data.feedback.status_name,
    resolved_by: .data.feedback.resolved_by
  }'

echo ""
echo ""
echo "=========================================="
echo "Test 2: Issue → Feedback Sync (Resolved)"
echo "=========================================="
echo ""

# Update issue #104 to "resolved" - should sync feedback to "resolved" with timestamps
echo "=== Updating Issue #104 to 'resolved' ==="
curl -X PUT "${API_URL}/admin/issues/104" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"status": "resolved"}' | jq '{
    issue_status: .data.issue.status,
    issue_resolved_by: .data.issue.resolved_by,
    issue_resolved_at: .data.issue.resolved_at
  }'

echo ""
echo "=== Checking if Feedback #4 synced to 'resolved' with timestamps ==="
curl -X GET "${API_URL}/admin/feedback/4" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" | jq '{
    id: .data.feedback.id,
    status: .data.feedback.status,
    status_name: .data.feedback.status_name,
    resolved_by: .data.feedback.resolved_by,
    resolved_at: .data.feedback.resolved_at
  }'

echo ""
echo ""
echo "=========================================="
echo "Test 3: Feedback → Issue Sync"
echo "=========================================="
echo ""

# Update feedback #4 to "pending" - should sync issue to "open"
echo "=== Updating Feedback #4 to 'pending' ==="
curl -X PATCH "${API_URL}/admin/feedback/4/status" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"status": "pending"}' | jq '{
    feedback_status: .data.feedback.status,
    feedback_status_name: .data.feedback.status_name
  }'

echo ""
echo "=== Checking if Issue #104 synced to 'open' ==="
curl -X GET "${API_URL}/admin/issues/104" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" | jq '{
    id: .data.issue.id,
    status: .data.issue.status,
    resolved_by: .data.issue.resolved_by
  }'

echo ""
echo ""
echo "=========================================="
echo "Summary: Bidirectional Sync Test Complete"
echo "=========================================="
