#!/bin/bash

API_URL="http://localhost:8000/api"
USER1_TOKEN="657|ud9ttNV5GiTqXLoTVP38AiihXJHFcXSW3JUxdf6T9896ac64"
USER2_TOKEN="658|oqkKljJv6YJ233shPzAPlV48B5ljfVA65umJ24MTafc08a67"
ADMIN_TOKEN="659|X4bnwI7kWgRJHT8rpMPtEwzaSt9G1GvKgnWmOp7wfedc2873"

PASS=0
FAIL=0

test_result() {
    if [ $1 -eq 0 ]; then
        echo "✓ PASS: $2"
        ((PASS++))
    else
        echo "✗ FAIL: $2"
        ((FAIL++))
    fi
}

echo "========================================"
echo "FINAL STATUS IMPLEMENTATION VERIFICATION"
echo "========================================"
echo ""

# TEST 1: Default status
echo "Test 1: Create note with default status..."
RESP=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"title":"T1","content":"test"}' "$API_URL/notes")
STATUS=$(echo "$RESP" | grep -o '"status":"[^"]*"' | tail -1 | cut -d'"' -f4)
[ "$STATUS" = "draft" ]
test_result $? "Default status is 'draft'"
echo ""

# TEST 2: Explicit published status
echo "Test 2: Create note with explicit published status..."
RESP=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"title":"T2 Public","content":"test","status":"published","is_private":false}' "$API_URL/notes")
STATUS=$(echo "$RESP" | grep -o '"status":"[^"]*"' | tail -1 | cut -d'"' -f4)
PUB_ID=$(echo "$RESP" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
[ "$STATUS" = "published" ]
test_result $? "Explicit published status works (ID: $PUB_ID)"
echo ""

# TEST 3: User2 can view published public note
echo "Test 3: Different user can view published public note..."
RESP=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $USER2_TOKEN" "$API_URL/notes/$PUB_ID")
echo "$RESP" | grep -q '"status":"success"'
test_result $? "User2 can view User1's published public note"
echo ""

# TEST 4: Create draft (will test visibility)
echo "Test 4: Create draft note for visibility test..."
RESP=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"title":"T4 Draft","content":"secret","status":"draft"}' "$API_URL/notes")
DRAFT_ID=$(echo "$RESP" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
STATUS=$(echo "$RESP" | grep -o '"status":"[^"]*"' | tail -1 | cut -d'"' -f4)
[ "$STATUS" = "draft" ]
test_result $? "Draft created successfully (ID: $DRAFT_ID)"
echo ""

# TEST 5: User2 CANNOT view draft
echo "Test 5: Different user CANNOT view draft..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" -H "Authorization: Bearer $USER2_TOKEN" "$API_URL/notes/$DRAFT_ID")
[ "$HTTP_CODE" = "403" ]
test_result $? "User2 correctly denied access to User1's draft (HTTP $HTTP_CODE)"
echo ""

# TEST 6: Owner can view own draft
echo "Test 6: Owner can view own draft..."
RESP=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $USER1_TOKEN" "$API_URL/notes/$DRAFT_ID")
echo "$RESP" | grep -q '"status":"success"'
test_result $? "Owner can view own draft"
echo ""

# TEST 7: Admin can view any draft
echo "Test 7: Admin can view any user's draft..."
RESP=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $ADMIN_TOKEN" "$API_URL/admin/notes/$DRAFT_ID")
echo "$RESP" | grep -q '"status":"success"'
test_result $? "Admin can view any draft"
echo ""

# TEST 8: Status filter - drafts
echo "Test 8: Filter my-notes by status=draft..."
RESP=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $USER1_TOKEN" "$API_URL/notes/my-notes?status=draft")
DRAFT_CNT=$(echo "$RESP" | grep -o '"status":"draft"' | wc -l)
PUB_CNT=$(echo "$RESP" | grep -o '"status":"published"' | wc -l)
[ "$PUB_CNT" -eq 0 ] && [ "$DRAFT_CNT" -gt 0 ]
test_result $? "Draft filter works (drafts: $DRAFT_CNT, published: $PUB_CNT)"
echo ""

# TEST 9: Status filter - published
echo "Test 9: Filter my-notes by status=published..."
RESP=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $USER1_TOKEN" "$API_URL/notes/my-notes?status=published")
DRAFT_CNT=$(echo "$RESP" | grep -o '"status":"draft"' | wc -l)
PUB_CNT=$(echo "$RESP" | grep -o '"status":"published"' | wc -l)
[ "$DRAFT_CNT" -eq 0 ] && [ "$PUB_CNT" -gt 0 ]
test_result $? "Published filter works (drafts: $DRAFT_CNT, published: $PUB_CNT)"
echo ""

# TEST 10: Update draft to published
echo "Test 10: Update draft to published..."
RESP=$(curl -s -X PUT -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"status":"published","is_private":false}' "$API_URL/notes/$DRAFT_ID")
NEW_STATUS=$(echo "$RESP" | grep -o '"status":"[^"]*"' | tail -1 | cut -d'"' -f4)
[ "$NEW_STATUS" = "published" ]
test_result $? "Status updated from draft to published"
echo ""

# TEST 11: User2 can now see the note
echo "Test 11: Different user can now view newly published note..."
RESP=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $USER2_TOKEN" "$API_URL/notes/$DRAFT_ID")
echo "$RESP" | grep -q '"status":"success"'
test_result $? "Note visible after status change to published"
echo ""

# TEST 12: Published private note
echo "Test 12: Create published private note..."
RESP=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"title":"T12 Private","content":"private","status":"published","is_private":true}' "$API_URL/notes")
PRIV_ID=$(echo "$RESP" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
IS_PRIV=$(echo "$RESP" | grep -o '"is_private":[a-z]*' | cut -d':' -f2)
[ "$IS_PRIV" = "true" ]
test_result $? "Published private note created (ID: $PRIV_ID)"
echo ""

# TEST 13: User2 cannot view published private
echo "Test 13: Different user CANNOT view published private note..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" -H "Authorization: Bearer $USER2_TOKEN" "$API_URL/notes/$PRIV_ID")
[ "$HTTP_CODE" = "403" ]
test_result $? "Published private note hidden from other users (HTTP $HTTP_CODE)"
echo ""

# TEST 14: Admin can view published private
echo "Test 14: Admin can view published private note..."
RESP=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $ADMIN_TOKEN" "$API_URL/admin/notes/$PRIV_ID")
echo "$RESP" | grep -q '"status":"success"'
test_result $? "Admin can view published private notes"
echo ""

# TEST 15: Invalid status rejected
echo "Test 15: Reject invalid status value..."
RESP=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $USER1_TOKEN" -d '{"title":"Invalid","content":"test","status":"pending"}' "$API_URL/notes")
echo "$RESP" | grep -q -E '"status":"error"|validation'
test_result $? "Invalid status correctly rejected"
echo ""

# TEST 16: Status field in response
echo "Test 16: Status field present in responses..."
RESP=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $USER1_TOKEN" "$API_URL/notes/my-notes")
echo "$RESP" | grep -q '"status":"draft"'
test_result $? "Status field present in list responses"
echo ""

echo "========================================"
echo "SUMMARY"
echo "========================================"
echo "Passed: $PASS"
echo "Failed: $FAIL"
echo ""
if [ $FAIL -eq 0 ]; then
    echo "✓ ALL TESTS PASSED!"
    exit 0
else
    echo "✗ SOME TESTS FAILED"
    exit 1
fi
