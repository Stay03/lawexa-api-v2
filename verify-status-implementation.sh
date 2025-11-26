#!/bin/bash

API_URL="http://localhost:8000/api"
USER1_TOKEN="657|ud9ttNV5GiTqXLoTVP38AiihXJHFcXSW3JUxdf6T9896ac64"
USER2_TOKEN="658|oqkKljJv6YJ233shPzAPlV48B5ljfVA65umJ24MTafc08a67"
ADMIN_TOKEN="659|X4bnwI7kWgRJHT8rpMPtEwzaSt9G1GvKgnWmOp7wfedc2873"

echo "========================================="
echo "CORE FUNCTIONALITY VERIFICATION"
echo "========================================="
echo ""

echo "1. Creating draft note (default status)..."
RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"title":"Verify Draft Default","content":"Test"}' "$API_URL/notes")
STATUS=$(echo "$RESPONSE" | python -m json.tool | grep '"status"' | tail -1 | grep -o '"status": "[^"]*"' | cut -d'"' -f4)
if [ "$STATUS" = "draft" ]; then
    echo "✓ PASS: Default status is 'draft'"
else
    echo "✗ FAIL: Expected 'draft', got '$STATUS'"
fi
echo ""

echo "2. Creating published note (explicit)..."
RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"title":"Verify Published","content":"Test","status":"published","is_private":false}' "$API_URL/notes")
STATUS=$(echo "$RESPONSE" | python -m json.tool | grep '"status"' | tail -1 | grep -o '"status": "[^"]*"' | cut -d'"' -f4)
NOTE_ID=$(echo "$RESPONSE" | python -m json.tool | grep '"id"' | head -1 | grep -o '[0-9]\+')
if [ "$STATUS" = "published" ]; then
    echo "✓ PASS: Explicit status 'published' works"
    echo "  Note ID: $NOTE_ID"
else
    echo "✗ FAIL: Expected 'published', got '$STATUS'"
fi
echo ""

echo "3. Verifying User2 can see published public note..."
RESPONSE=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $USER2_TOKEN" "$API_URL/notes/$NOTE_ID")
if echo "$RESPONSE" | grep -q '"status":"success"'; then
    echo "✓ PASS: User2 can view published public note"
else
    echo "✗ FAIL: User2 cannot view published public note"
fi
echo ""

echo "4. Creating User1's draft note..."
RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"title":"User1 Secret Draft","content":"User2 should not see this","status":"draft"}' "$API_URL/notes")
DRAFT_ID=$(echo "$RESPONSE" | python -m json.tool | grep '"id"' | head -1 | grep -o '[0-9]\+')
echo "  Draft ID: $DRAFT_ID"
echo ""

echo "5. Verifying User2 CANNOT see User1's draft..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" -H "Authorization: Bearer $USER2_TOKEN" "$API_URL/notes/$DRAFT_ID")
if [ "$HTTP_CODE" = "403" ] || [ "$HTTP_CODE" = "404" ]; then
    echo "✓ PASS: User2 cannot view User1's draft (HTTP $HTTP_CODE)"
else
    echo "✗ FAIL: User2 should not access draft (got HTTP $HTTP_CODE)"
fi
echo ""

echo "6. Verifying Admin CAN see User1's draft..."
RESPONSE=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $ADMIN_TOKEN" "$API_URL/admin/notes/$DRAFT_ID")
if echo "$RESPONSE" | grep -q '"status":"success"'; then
    echo "✓ PASS: Admin can view any user's draft"
else
    echo "✗ FAIL: Admin should view all drafts"
fi
echo ""

echo "7. Testing status filter on my-notes..."
RESPONSE=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $USER1_TOKEN" "$API_URL/notes/my-notes?status=draft")
DRAFT_COUNT=$(echo "$RESPONSE" | grep -o '"status":"draft"' | wc -l)
PUB_COUNT=$(echo "$RESPONSE" | grep -o '"status":"published"' | wc -l)
if [ "$PUB_COUNT" -eq 0 ] && [ "$DRAFT_COUNT" -gt 0 ]; then
    echo "✓ PASS: Status filter works (found $DRAFT_COUNT drafts, 0 published)"
else
    echo "✗ FAIL: Status filter not working (drafts: $DRAFT_COUNT, published: $PUB_COUNT)"
fi
echo ""

echo "8. Updating draft to published..."
RESPONSE=$(curl -s -X PUT -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"status":"published","is_private":false}' "$API_URL/notes/$DRAFT_ID")
NEW_STATUS=$(echo "$RESPONSE" | python -m json.tool | grep '"status"' | tail -1 | grep -o '"status": "[^"]*"' | cut -d'"' -f4)
if [ "$NEW_STATUS" = "published" ]; then
    echo "✓ PASS: Status updated from draft to published"
else
    echo "✗ FAIL: Status update failed (got '$NEW_STATUS')"
fi
echo ""

echo "9. Verifying User2 CAN NOW see the published note..."
RESPONSE=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $USER2_TOKEN" "$API_URL/notes/$DRAFT_ID")
if echo "$RESPONSE" | grep -q '"status":"success"'; then
    echo "✓ PASS: Note is now visible after publishing"
else
    echo "✗ FAIL: Published note should be visible"
fi
echo ""

echo "10. Creating published PRIVATE note..."
RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"title":"Private Published","content":"Test","status":"published","is_private":true}' "$API_URL/notes")
PRIVATE_ID=$(echo "$RESPONSE" | python -m json.tool | grep '"id"' | head -1 | grep -o '[0-9]\+')
echo "  Private note ID: $PRIVATE_ID"
echo ""

echo "11. Verifying User2 CANNOT see published private note..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" -H "Authorization: Bearer $USER2_TOKEN" "$API_URL/notes/$PRIVATE_ID")
if [ "$HTTP_CODE" = "403" ] || [ "$HTTP_CODE" = "404" ]; then
    echo "✓ PASS: Published private note hidden from other users (HTTP $HTTP_CODE)"
else
    echo "✗ FAIL: Published private should be hidden (got HTTP $HTTP_CODE)"
fi
echo ""

echo "12. Validating invalid status is rejected..."
RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"title":"Invalid","content":"Test","status":"pending"}' "$API_URL/notes")
if echo "$RESPONSE" | grep -q '"status":"error"'; then
    echo "✓ PASS: Invalid status correctly rejected"
else
    echo "✗ FAIL: Should reject invalid status values"
fi
echo ""

echo "========================================="
echo "VERIFICATION COMPLETE"
echo "========================================="
