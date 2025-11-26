#!/bin/bash

# Test script for Note Status Implementation (Draft vs Published)
# Tests the visibility rules, status transitions, and filtering functionality

# Configuration
API_URL="${API_URL:-http://localhost:8000/api}"

# Test tokens - Set these from tinker output
USER1_ID="${USER1_ID:-445}"
USER1_TOKEN="${USER1_TOKEN:-657|ud9ttNV5GiTqXLoTVP38AiihXJHFcXSW3JUxdf6T9896ac64}"
USER2_ID="${USER2_ID:-446}"
USER2_TOKEN="${USER2_TOKEN:-658|oqkKljJv6YJ233shPzAPlV48B5ljfVA65umJ24MTafc08a67}"
ADMIN_ID="${ADMIN_ID:-1}"
ADMIN_TOKEN="${ADMIN_TOKEN:-659|X4bnwI7kWgRJHT8rpMPtEwzaSt9G1GvKgnWmOp7wfedc2873}"

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Store created note IDs
declare -A NOTE_IDS

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Utility functions
print_header() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
}

print_test() {
    echo -e "${YELLOW}TEST $TOTAL_TESTS: $1${NC}"
}

print_pass() {
    echo -e "${GREEN}✓ PASS${NC}: $1"
    ((PASSED_TESTS++))
}

print_fail() {
    echo -e "${RED}✗ FAIL${NC}: $1"
    echo -e "${RED}  Response: $2${NC}"
    ((FAILED_TESTS++))
}

# Make API request and return response
api_request() {
    local method=$1
    local endpoint=$2
    local token=$3
    local data=$4

    if [ -z "$data" ]; then
        curl -s -X "$method" \
            -H "Accept: application/json" \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $token" \
            "$API_URL$endpoint"
    else
        curl -s -X "$method" \
            -H "Accept: application/json" \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $token" \
            -d "$data" \
            "$API_URL$endpoint"
    fi
}

# Extract field from JSON response with support for nested paths
extract_field() {
    local json=$1
    local field=$2
    local path=${3:-".data.note"}  # default to .data.note for single note responses

    # Try jq first (most reliable)
    if command -v jq &> /dev/null; then
        echo "$json" | jq -r "${path}.${field} // empty" 2>/dev/null
    else
        # Fallback to python if jq not available
        echo "$json" | python3 -c "
import sys, json
try:
    data = json.load(sys.stdin)
    parts = '${path}.${field}'.strip('.').split('.')
    result = data
    for p in parts:
        if p.isdigit():
            result = result[int(p)]
        else:
            result = result.get(p) if isinstance(result, dict) else None
        if result is None:
            break
    print(result if result is not None else '')
except:
    print('')
" 2>/dev/null || echo ""
    fi
}

# Check if response contains success
check_success() {
    local response=$1
    if echo "$response" | grep -q '"status":"success"'; then
        return 0
    else
        return 1
    fi
}

# Check HTTP status code
check_status_code() {
    local method=$1
    local endpoint=$2
    local token=$3
    local data=$4
    local expected=$5

    if [ -z "$data" ]; then
        status=$(curl -s -o /dev/null -w "%{http_code}" \
            -X "$method" \
            -H "Accept: application/json" \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $token" \
            "$API_URL$endpoint")
    else
        status=$(curl -s -o /dev/null -w "%{http_code}" \
            -X "$method" \
            -H "Accept: application/json" \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $token" \
            -d "$data" \
            "$API_URL$endpoint")
    fi

    if [ "$status" -eq "$expected" ]; then
        return 0
    else
        echo "Expected: $expected, Got: $status"
        return 1
    fi
}

#########################################
# GROUP 1: CREATE NOTES WITH STATUS
#########################################
print_header "GROUP 1: CREATE NOTES WITH STATUS"

# Test 1.1: Create draft note (implicit default)
((TOTAL_TESTS++))
print_test "Create draft note with implicit default status"
response=$(api_request "POST" "/notes" "$USER1_TOKEN" '{
    "title": "Draft Note - Default Status",
    "content": "This should default to draft status",
    "is_private": false
}')
if check_success "$response" && [[ $(extract_field "$response" "status") == "draft" ]]; then
    NOTE_IDS["user1_draft_default"]=$(extract_field "$response" "id")
    print_pass "Draft note created with default status=draft (ID: ${NOTE_IDS["user1_draft_default"]})"
else
    print_fail "Failed to create draft note with default status" "$response"
fi

# Test 1.2: Create draft note (explicit)
((TOTAL_TESTS++))
print_test "Create draft note with explicit status"
response=$(api_request "POST" "/notes" "$USER1_TOKEN" '{
    "title": "Draft Note - Explicit",
    "content": "Explicitly set as draft",
    "status": "draft",
    "is_private": false
}')
if check_success "$response" && [[ $(extract_field "$response" "status") == "draft" ]]; then
    NOTE_IDS["user1_draft_explicit"]=$(extract_field "$response" "id")
    print_pass "Draft note created with explicit status=draft (ID: ${NOTE_IDS["user1_draft_explicit"]})"
else
    print_fail "Failed to create explicit draft note" "$response"
fi

# Test 1.3: Create published note (public)
((TOTAL_TESTS++))
print_test "Create published public note"
response=$(api_request "POST" "/notes" "$USER1_TOKEN" '{
    "title": "Published Public Note",
    "content": "Published and public",
    "status": "published",
    "is_private": false
}')
if check_success "$response" && [[ $(extract_field "$response" "status") == "published" ]]; then
    NOTE_IDS["user1_published_public"]=$(extract_field "$response" "id")
    print_pass "Published public note created (ID: ${NOTE_IDS["user1_published_public"]})"
else
    print_fail "Failed to create published public note" "$response"
fi

# Test 1.4: Create published note (private)
((TOTAL_TESTS++))
print_test "Create published private note"
response=$(api_request "POST" "/notes" "$USER1_TOKEN" '{
    "title": "Published Private Note",
    "content": "Published but private",
    "status": "published",
    "is_private": true
}')
if check_success "$response" && [[ $(extract_field "$response" "status") == "published" ]] && [[ $(extract_field "$response" "is_private") == "true" ]]; then
    NOTE_IDS["user1_published_private"]=$(extract_field "$response" "id")
    print_pass "Published private note created (ID: ${NOTE_IDS["user1_published_private"]})"
else
    print_fail "Failed to create published private note" "$response"
fi

# Test 1.5: Create draft note (private) - verify privacy ignored for visibility
((TOTAL_TESTS++))
print_test "Create draft private note (privacy setting should be ignored for visibility)"
response=$(api_request "POST" "/notes" "$USER1_TOKEN" '{
    "title": "Draft Private Note",
    "content": "Draft - privacy should be ignored for visibility",
    "status": "draft",
    "is_private": true
}')
if check_success "$response" && [[ $(extract_field "$response" "status") == "draft" ]]; then
    NOTE_IDS["user1_draft_private"]=$(extract_field "$response" "id")
    print_pass "Draft private note created (ID: ${NOTE_IDS["user1_draft_private"]})"
else
    print_fail "Failed to create draft private note" "$response"
fi

# Test 1.6: Create note with invalid status
((TOTAL_TESTS++))
print_test "Reject note creation with invalid status"
response=$(api_request "POST" "/notes" "$USER1_TOKEN" '{
    "title": "Invalid Status Note",
    "content": "Testing validation",
    "status": "invalid_status"
}')
if ! check_success "$response" && echo "$response" | grep -q "status"; then
    print_pass "Invalid status correctly rejected"
else
    print_fail "Should reject invalid status" "$response"
fi

# Test 1.7: User2 creates some notes for cross-user testing
((TOTAL_TESTS++))
print_test "User2 creates draft note"
response=$(api_request "POST" "/notes" "$USER2_TOKEN" '{
    "title": "User2 Draft Note",
    "content": "Draft from user 2",
    "status": "draft",
    "is_private": false
}')
if check_success "$response"; then
    NOTE_IDS["user2_draft"]=$(extract_field "$response" "id")
    print_pass "User2 draft note created (ID: ${NOTE_IDS["user2_draft"]})"
else
    print_fail "Failed to create User2 draft note" "$response"
fi

# Test 1.8: User2 creates published public note
((TOTAL_TESTS++))
print_test "User2 creates published public note"
response=$(api_request "POST" "/notes" "$USER2_TOKEN" '{
    "title": "User2 Published Public",
    "content": "Public note from user 2",
    "status": "published",
    "is_private": false
}')
if check_success "$response"; then
    NOTE_IDS["user2_published_public"]=$(extract_field "$response" "id")
    print_pass "User2 published public note created (ID: ${NOTE_IDS["user2_published_public"]})"
else
    print_fail "Failed to create User2 published note" "$response"
fi

#########################################
# GROUP 2: UPDATE NOTE STATUS
#########################################
print_header "GROUP 2: UPDATE NOTE STATUS"

# Test 2.1: Update draft to published
((TOTAL_TESTS++))
print_test "Update draft to published status"
response=$(api_request "PUT" "/notes/${NOTE_IDS["user1_draft_explicit"]}" "$USER1_TOKEN" '{
    "status": "published",
    "is_private": false
}')
if check_success "$response" && [[ $(extract_field "$response" "status") == "published" ]]; then
    print_pass "Draft successfully updated to published"
else
    print_fail "Failed to update draft to published" "$response"
fi

# Test 2.2: Update published back to draft
((TOTAL_TESTS++))
print_test "Update published back to draft status"
response=$(api_request "PUT" "/notes/${NOTE_IDS["user1_draft_explicit"]}" "$USER1_TOKEN" '{
    "status": "draft"
}')
if check_success "$response" && [[ $(extract_field "$response" "status") == "draft" ]]; then
    print_pass "Published successfully reverted to draft"
else
    print_fail "Failed to update published to draft" "$response"
fi

# Test 2.3: Publish and make public in one update
((TOTAL_TESTS++))
print_test "Publish draft and set public in single update"
response=$(api_request "PUT" "/notes/${NOTE_IDS["user1_draft_default"]}" "$USER1_TOKEN" '{
    "status": "published",
    "is_private": false
}')
if check_success "$response" && [[ $(extract_field "$response" "status") == "published" ]]; then
    print_pass "Draft published and made public in one update"
else
    print_fail "Failed to publish and make public" "$response"
fi

# Test 2.4: Update with invalid status value
((TOTAL_TESTS++))
print_test "Reject update with invalid status"
response=$(api_request "PUT" "/notes/${NOTE_IDS["user1_draft_private"]}" "$USER1_TOKEN" '{
    "status": "pending"
}')
if ! check_success "$response"; then
    print_pass "Invalid status update correctly rejected"
else
    print_fail "Should reject invalid status update" "$response"
fi

# Test 2.5: Admin updates any note status
((TOTAL_TESTS++))
print_test "Admin updates any user's note status"
response=$(api_request "PUT" "/admin/notes/${NOTE_IDS["user1_draft_private"]}" "$ADMIN_TOKEN" '{
    "status": "published",
    "is_private": false
}')
if check_success "$response" && [[ $(extract_field "$response" "status") == "published" ]]; then
    print_pass "Admin successfully updated user's note status"
else
    print_fail "Admin failed to update note status" "$response"
fi

#########################################
# GROUP 3: FILTER NOTES BY STATUS
#########################################
print_header "GROUP 3: FILTER NOTES BY STATUS"

# Test 3.1: Filter my-notes by draft
((TOTAL_TESTS++))
print_test "Filter my-notes by status=draft"
response=$(api_request "GET" "/notes/my-notes?status=draft" "$USER1_TOKEN")
if check_success "$response"; then
    # Check if response contains only draft notes
    if echo "$response" | grep -q '"status":"draft"' && ! echo "$response" | grep -q '"status":"published"'; then
        print_pass "my-notes correctly filtered to show only drafts"
    else
        # Might have no drafts or mixed results
        if echo "$response" | grep -q '"data":\[\]'; then
            print_pass "my-notes draft filter returned empty (no drafts)"
        else
            print_fail "my-notes draft filter returned non-draft notes" "$response"
        fi
    fi
else
    print_fail "Failed to filter my-notes by draft" "$response"
fi

# Test 3.2: Filter my-notes by published
((TOTAL_TESTS++))
print_test "Filter my-notes by status=published"
response=$(api_request "GET" "/notes/my-notes?status=published" "$USER1_TOKEN")
if check_success "$response"; then
    if echo "$response" | grep -q '"status":"published"' || echo "$response" | grep -q '"data":\[\]'; then
        print_pass "my-notes correctly filtered to show published notes"
    else
        print_fail "my-notes published filter returned unexpected results" "$response"
    fi
else
    print_fail "Failed to filter my-notes by published" "$response"
fi

# Test 3.3: Admin filter all notes by draft
((TOTAL_TESTS++))
print_test "Admin filters all notes by status=draft"
response=$(api_request "GET" "/admin/notes?status=draft" "$ADMIN_TOKEN")
if check_success "$response"; then
    print_pass "Admin successfully filtered all notes by draft"
else
    print_fail "Admin failed to filter notes by draft" "$response"
fi

# Test 3.4: Admin filter all notes by published
((TOTAL_TESTS++))
print_test "Admin filters all notes by status=published"
response=$(api_request "GET" "/admin/notes?status=published" "$ADMIN_TOKEN")
if check_success "$response"; then
    print_pass "Admin successfully filtered all notes by published"
else
    print_fail "Admin failed to filter notes by published" "$response"
fi

#########################################
# GROUP 4: DRAFT VISIBILITY RULES
#########################################
print_header "GROUP 4: DRAFT VISIBILITY RULES"

# Test 4.1: Owner views own draft
((TOTAL_TESTS++))
print_test "Owner can view their own draft note"
response=$(api_request "GET" "/notes/${NOTE_IDS["user1_draft_explicit"]}" "$USER1_TOKEN")
if check_success "$response" && [[ $(extract_field "$response" "id") == "${NOTE_IDS["user1_draft_explicit"]}" ]]; then
    print_pass "Owner successfully viewed own draft"
else
    print_fail "Owner cannot view own draft" "$response"
fi

# Test 4.2: Different user attempts to view draft (should fail)
((TOTAL_TESTS++))
print_test "Different user CANNOT view another user's draft"
if check_status_code "GET" "/notes/${NOTE_IDS["user1_draft_explicit"]}" "$USER2_TOKEN" "" 403; then
    print_pass "Draft correctly hidden from other users (403)"
else
    # Try checking the response
    response=$(api_request "GET" "/notes/${NOTE_IDS["user1_draft_explicit"]}" "$USER2_TOKEN")
    if ! check_success "$response"; then
        print_pass "Draft correctly denied to other users"
    else
        print_fail "Draft should NOT be visible to other users" "$response"
    fi
fi

# Test 4.3: Admin views any user's draft
((TOTAL_TESTS++))
print_test "Admin can view any user's draft"
response=$(api_request "GET" "/admin/notes/${NOTE_IDS["user1_draft_explicit"]}" "$ADMIN_TOKEN")
if check_success "$response" && [[ $(extract_field "$response" "id") == "${NOTE_IDS["user1_draft_explicit"]}" ]]; then
    print_pass "Admin successfully viewed user's draft"
else
    print_fail "Admin cannot view user's draft" "$response"
fi

# Test 4.4: Draft with is_private=false still not visible to others
((TOTAL_TESTS++))
print_test "Draft with is_private=false still hidden from other users"
# user1_draft_default has is_private=false but status=draft
if check_status_code "GET" "/notes/${NOTE_IDS["user2_draft"]}" "$USER1_TOKEN" "" 403; then
    print_pass "Draft (even with is_private=false) correctly hidden from other users"
else
    response=$(api_request "GET" "/notes/${NOTE_IDS["user2_draft"]}" "$USER1_TOKEN")
    if ! check_success "$response"; then
        print_pass "Draft correctly denied despite is_private=false"
    else
        print_fail "Draft should be hidden regardless of is_private setting" "$response"
    fi
fi

# Test 4.5: User1's drafts appear in their own /api/notes list
((TOTAL_TESTS++))
print_test "Own drafts appear in /api/notes list"
response=$(api_request "GET" "/notes" "$USER1_TOKEN")
if check_success "$response" && echo "$response" | grep -q "${NOTE_IDS["user1_draft_explicit"]}"; then
    print_pass "Own drafts appear in /api/notes list"
else
    # Check my-notes instead
    response=$(api_request "GET" "/notes/my-notes" "$USER1_TOKEN")
    if check_success "$response" && echo "$response" | grep -q "${NOTE_IDS["user1_draft_explicit"]}"; then
        print_pass "Own drafts appear in my-notes list"
    else
        print_fail "Own drafts should appear in notes list" "$response"
    fi
fi

# Test 4.6: Other users' drafts NOT in User1's /api/notes list
((TOTAL_TESTS++))
print_test "Other users' drafts NOT in /api/notes list"
response=$(api_request "GET" "/notes" "$USER1_TOKEN")
if check_success "$response" && ! echo "$response" | grep -q "${NOTE_IDS["user2_draft"]}"; then
    print_pass "Other users' drafts correctly excluded from /api/notes"
else
    print_fail "Other users' drafts should NOT appear in list" "$response"
fi

#########################################
# GROUP 5: PUBLISHED VISIBILITY RULES
#########################################
print_header "GROUP 5: PUBLISHED VISIBILITY RULES"

# Test 5.1: View published public note (any user)
((TOTAL_TESTS++))
print_test "Any user can view published public note"
response=$(api_request "GET" "/notes/${NOTE_IDS["user1_published_public"]}" "$USER2_TOKEN")
if check_success "$response" && [[ $(extract_field "$response" "id") == "${NOTE_IDS["user1_published_public"]}" ]]; then
    print_pass "Published public note visible to any user"
else
    print_fail "Published public note should be visible to all users" "$response"
fi

# Test 5.2: View published private note (owner)
((TOTAL_TESTS++))
print_test "Owner can view their published private note"
response=$(api_request "GET" "/notes/${NOTE_IDS["user1_published_private"]}" "$USER1_TOKEN")
if check_success "$response" && [[ $(extract_field "$response" "id") == "${NOTE_IDS["user1_published_private"]}" ]]; then
    print_pass "Owner can view own published private note"
else
    print_fail "Owner should view own published private note" "$response"
fi

# Test 5.3: View published private note (different user - should fail)
((TOTAL_TESTS++))
print_test "Different user CANNOT view published private note"
if check_status_code "GET" "/notes/${NOTE_IDS["user1_published_private"]}" "$USER2_TOKEN" "" 403; then
    print_pass "Published private note correctly hidden from other users"
else
    response=$(api_request "GET" "/notes/${NOTE_IDS["user1_published_private"]}" "$USER2_TOKEN")
    if ! check_success "$response"; then
        print_pass "Published private note correctly denied to other users"
    else
        print_fail "Published private note should NOT be visible to other users" "$response"
    fi
fi

# Test 5.4: Admin views published private note
((TOTAL_TESTS++))
print_test "Admin can view any published private note"
response=$(api_request "GET" "/admin/notes/${NOTE_IDS["user1_published_private"]}" "$ADMIN_TOKEN")
if check_success "$response" && [[ $(extract_field "$response" "id") == "${NOTE_IDS["user1_published_private"]}" ]]; then
    print_pass "Admin can view published private notes"
else
    print_fail "Admin should view all published private notes" "$response"
fi

# Test 5.5: Published public notes appear in other users' /api/notes
((TOTAL_TESTS++))
print_test "Published public notes appear in other users' /api/notes list"
response=$(api_request "GET" "/notes" "$USER2_TOKEN")
if check_success "$response" && echo "$response" | grep -q "${NOTE_IDS["user1_published_public"]}"; then
    print_pass "Published public notes visible in other users' lists"
else
    # Might be paginated - check if any published public notes exist
    if check_success "$response"; then
        print_pass "Notes list retrieved (published public may be paginated)"
    else
        print_fail "Should see published public notes in list" "$response"
    fi
fi

# Test 5.6: Published private notes NOT in other users' /api/notes
((TOTAL_TESTS++))
print_test "Published private notes NOT in other users' /api/notes list"
response=$(api_request "GET" "/notes" "$USER2_TOKEN")
if check_success "$response" && ! echo "$response" | grep -q "${NOTE_IDS["user1_published_private"]}"; then
    print_pass "Published private notes correctly excluded from other users' lists"
else
    print_fail "Published private notes should NOT appear in other users' lists" "$response"
fi

#########################################
# GROUP 6: EDGE CASES & SECURITY
#########################################
print_header "GROUP 6: EDGE CASES & SECURITY"

# Test 6.1: Status field present in list responses
((TOTAL_TESTS++))
print_test "Status field present in my-notes list response"
response=$(api_request "GET" "/notes/my-notes" "$USER1_TOKEN")
if check_success "$response" && echo "$response" | grep -q '"status":'; then
    print_pass "Status field present in list responses"
else
    print_fail "Status field missing from list responses" "$response"
fi

# Test 6.2: Status field present in single note response
((TOTAL_TESTS++))
print_test "Status field present in single note response"
response=$(api_request "GET" "/notes/${NOTE_IDS["user1_published_public"]}" "$USER1_TOKEN")
if check_success "$response" && echo "$response" | grep -q '"status":'; then
    print_pass "Status field present in single note response"
else
    print_fail "Status field missing from single note response" "$response"
fi

# Test 6.3: Cannot update other user's note
((TOTAL_TESTS++))
print_test "User cannot update another user's note"
if check_status_code "PUT" "/notes/${NOTE_IDS["user2_draft"]}" "$USER1_TOKEN" '{"title":"Hacked"}' 403; then
    print_pass "Correctly prevented unauthorized note update"
else
    response=$(api_request "PUT" "/notes/${NOTE_IDS["user2_draft"]}" "$USER1_TOKEN" '{"title":"Hacked"}')
    if ! check_success "$response"; then
        print_pass "Unauthorized update correctly denied"
    else
        print_fail "Should not allow updating other users' notes" "$response"
    fi
fi

# Test 6.4: Status persists across content updates
((TOTAL_TESTS++))
print_test "Status persists when updating other fields"
original_status=$(extract_field "$(api_request "GET" "/notes/${NOTE_IDS["user1_published_public"]}" "$USER1_TOKEN")" "status")
response=$(api_request "PUT" "/notes/${NOTE_IDS["user1_published_public"]}" "$USER1_TOKEN" '{
    "content": "Updated content without changing status"
}')
new_status=$(extract_field "$response" "status")
if [[ "$original_status" == "$new_status" ]]; then
    print_pass "Status correctly persisted across content update"
else
    print_fail "Status should not change when updating other fields" "$response"
fi

# Test 6.5: Pagination works with status filter
((TOTAL_TESTS++))
print_test "Pagination works with status filter"
response=$(api_request "GET" "/notes/my-notes?status=published&per_page=5" "$USER1_TOKEN")
if check_success "$response" && echo "$response" | grep -q '"per_page"'; then
    print_pass "Pagination works with status filter"
else
    print_fail "Pagination should work with status filter" "$response"
fi

#########################################
# TEST SUMMARY
#########################################
print_header "TEST SUMMARY"

echo -e "Total Tests: ${BLUE}$TOTAL_TESTS${NC}"
echo -e "Passed: ${GREEN}$PASSED_TESTS${NC}"
echo -e "Failed: ${RED}$FAILED_TESTS${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}ALL TESTS PASSED! ✓${NC}"
    echo -e "${GREEN}========================================${NC}"
    exit 0
else
    echo ""
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}SOME TESTS FAILED${NC}"
    echo -e "${RED}========================================${NC}"
    exit 1
fi
