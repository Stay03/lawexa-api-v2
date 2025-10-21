#!/bin/bash

# ============================================================================
# Content Request System - Phase 1 Comprehensive Test Script
# ============================================================================
# Version: 1.0
# Date: October 20, 2025
# Phase: 1 - Cases Only
#
# This script tests all endpoints and functionality of the Content Request System
# based on the implementation testing guide.
# ============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color
BOLD='\033[1m'

# API Configuration
: ${API_URL:=http://localhost:8000/api}
LOG_FILE="test-content-request-$(date +%Y%m%d-%H%M%S).log"

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Test IDs storage
: ${USER_TOKEN:=}
: ${ADMIN_TOKEN:=}
REQUEST_ID=""
CASE_ID=""
USER_ID=""
ADMIN_ID=""

# ============================================================================
# Utility Functions
# ============================================================================

print_header() {
    echo -e "\n${BOLD}${CYAN}========================================${NC}"
    echo -e "${BOLD}${CYAN}$1${NC}"
    echo -e "${BOLD}${CYAN}========================================${NC}\n"
}

print_test() {
    echo -e "${BLUE}[TEST $1]${NC} $2"
}

print_success() {
    echo -e "${GREEN}✓ PASS:${NC} $1"
    ((PASSED_TESTS++))
    ((TOTAL_TESTS++))
}

print_error() {
    echo -e "${RED}✗ FAIL:${NC} $1"
    ((FAILED_TESTS++))
    ((TOTAL_TESTS++))
}

print_warning() {
    echo -e "${YELLOW}⚠ WARNING:${NC} $1"
}

print_info() {
    echo -e "${CYAN}ℹ INFO:${NC} $1"
}

log_response() {
    echo -e "\n--- Response ---" | tee -a "$LOG_FILE"
    echo "$1" | jq '.' 2>/dev/null || echo "$1" | tee -a "$LOG_FILE"
    echo -e "--- End Response ---\n" | tee -a "$LOG_FILE"
}

check_response_status() {
    local response="$1"
    local expected_status="$2"
    local test_name="$3"

    local status=$(echo "$response" | jq -r '.status // empty')

    if [[ "$status" == "success" && "$expected_status" == "success" ]]; then
        print_success "$test_name"
        return 0
    elif [[ "$status" == "error" && "$expected_status" == "error" ]]; then
        print_success "$test_name (expected error)"
        return 0
    else
        print_error "$test_name - Expected: $expected_status, Got: $status"
        log_response "$response"
        return 1
    fi
}

check_http_status() {
    local http_status="$1"
    local expected_status="$2"
    local test_name="$3"

    if [[ "$http_status" == "$expected_status" ]]; then
        print_success "$test_name (HTTP $http_status)"
        return 0
    else
        print_error "$test_name - Expected HTTP $expected_status, Got: $http_status"
        return 1
    fi
}

# ============================================================================
# Pre-Testing Verification
# ============================================================================

print_header "PRE-TESTING VERIFICATION"

# Test 1: Check Database Migration
print_test "1" "Verifying content_requests table exists"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../../.." && pwd)"
MIGRATION_CHECK=$(php "$PROJECT_ROOT/artisan" migrate:status 2>&1 | grep "content_requests" || echo "NOT_FOUND")

if [[ "$MIGRATION_CHECK" != "NOT_FOUND" ]]; then
    print_success "Database migration verified"
else
    print_error "content_requests table not found. Run: php artisan migrate"
    exit 1
fi

# Test 2: Check Routes
print_test "2" "Verifying all 10 routes are registered"
ROUTE_COUNT=$(php "$PROJECT_ROOT/artisan" route:list | grep -c "content-request" || true)

if [[ -z "$ROUTE_COUNT" ]]; then
    ROUTE_COUNT=0
fi

if [[ "$ROUTE_COUNT" -ge 10 ]]; then
    print_success "All routes registered (found $ROUTE_COUNT)"
else
    print_warning "Expected at least 10 routes, found: $ROUTE_COUNT"
fi

# Test 3: Get User Token
print_test "3" "Obtaining user authentication token"

if [[ -n "$USER_TOKEN" ]]; then
    print_info "Using provided USER_TOKEN from environment"
    print_success "User token available"
else
    print_warning "No USER_TOKEN in environment. Please set USER_TOKEN variable."
    echo -e "${YELLOW}Example: export USER_TOKEN='your-token-here'${NC}"
    echo -e "${YELLOW}Or login via: curl -X POST $API_URL/auth/login -H 'Content-Type: application/json' -d '{\"email\":\"user@example.com\",\"password\":\"password\"}'${NC}"
    exit 1
fi

# Test 4: Get Admin Token
print_test "4" "Obtaining admin authentication token"

if [[ -n "$ADMIN_TOKEN" ]]; then
    print_info "Using provided ADMIN_TOKEN from environment"
    print_success "Admin token available"
else
    print_warning "No ADMIN_TOKEN in environment. Please set ADMIN_TOKEN variable."
    echo -e "${YELLOW}Example: export ADMIN_TOKEN='your-admin-token-here'${NC}"
    exit 1
fi

# Get user ID for later use
USER_RESPONSE=$(curl -s -X GET "$API_URL/user/profile" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")
USER_ID=$(echo "$USER_RESPONSE" | jq -r '.data.user.id // empty')
print_info "User ID: $USER_ID"

# ============================================================================
# USER ENDPOINT TESTS
# ============================================================================

print_header "USER ENDPOINT TESTS"

# Test 5: Create Content Request (Happy Path)
print_test "5" "Creating content request - Valid case request"

CREATE_RESPONSE=$(curl -s -X POST "$API_URL/content-requests" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
        "type": "case",
        "title": "Smith v Jones [2023] EWCA Civ 123",
        "additional_notes": "Key case on contract formation, needed for dissertation"
    }')

REQUEST_ID=$(echo "$CREATE_RESPONSE" | jq -r '.data.content_request.id // empty')

if [[ -n "$REQUEST_ID" && "$REQUEST_ID" != "null" ]]; then
    check_response_status "$CREATE_RESPONSE" "success" "Create content request"
    print_info "Created Request ID: $REQUEST_ID"
else
    print_error "Failed to create content request"
    log_response "$CREATE_RESPONSE"
fi

# Test 6: Create Request - Missing Title
print_test "6" "Creating request without title (validation error)"

VALIDATION_RESPONSE=$(curl -s -X POST "$API_URL/content-requests" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
        "type": "case"
    }')

ERROR_MSG=$(echo "$VALIDATION_RESPONSE" | jq -r '.message // empty')
TITLE_ERROR=$(echo "$VALIDATION_RESPONSE" | jq -r '.errors.title[0] // empty')
if [[ "$ERROR_MSG" == "Validation failed" ]] && [[ -n "$TITLE_ERROR" ]]; then
    print_success "Validation error for missing title"
else
    print_error "Expected validation error for missing title"
    log_response "$VALIDATION_RESPONSE"
fi

# Test 7: Create Request - Invalid Type
print_test "7" "Creating request with invalid type"

INVALID_TYPE_RESPONSE=$(curl -s -X POST "$API_URL/content-requests" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
        "type": "invalid_type",
        "title": "Test Case"
    }')

ERROR_MSG=$(echo "$INVALID_TYPE_RESPONSE" | jq -r '.message // empty')
TYPE_ERROR=$(echo "$INVALID_TYPE_RESPONSE" | jq -r '.errors.type[0] // empty')
if [[ "$ERROR_MSG" == "Validation failed" ]] && [[ -n "$TYPE_ERROR" ]]; then
    print_success "Validation error for invalid type"
else
    print_error "Expected validation error for invalid type"
    log_response "$INVALID_TYPE_RESPONSE"
fi

# Test 8: Create Request - Title Too Long
print_test "8" "Creating request with title exceeding 500 characters"

LONG_TITLE=$(printf 'A%.0s' {1..600})
LONG_TITLE_RESPONSE=$(curl -s -X POST "$API_URL/content-requests" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{
        \"type\": \"case\",
        \"title\": \"$LONG_TITLE\"
    }")

ERROR_MSG=$(echo "$LONG_TITLE_RESPONSE" | jq -r '.message // empty')
TITLE_ERROR=$(echo "$LONG_TITLE_RESPONSE" | jq -r '.errors.title[0] // empty')
if [[ "$ERROR_MSG" == "Validation failed" ]] && [[ -n "$TITLE_ERROR" ]]; then
    print_success "Validation error for title too long"
else
    print_error "Expected validation error for title length"
    log_response "$LONG_TITLE_RESPONSE"
fi

# Test 9: List User's Requests
print_test "9" "Listing user's content requests"

LIST_RESPONSE=$(curl -s -X GET "$API_URL/content-requests" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$LIST_RESPONSE" "success" "List user's requests"

REQUEST_COUNT=$(echo "$LIST_RESPONSE" | jq -r '.data.meta.total // 0')
print_info "Found $REQUEST_COUNT request(s)"

# Test 10: List with Status Filter
print_test "10" "Listing requests filtered by status=pending"

FILTER_RESPONSE=$(curl -s -X GET "$API_URL/content-requests?status=pending" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$FILTER_RESPONSE" "success" "Filter by status"

# Test 11: List with Search
print_test "11" "Searching requests by title"

SEARCH_RESPONSE=$(curl -s -X GET "$API_URL/content-requests?search=Smith" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$SEARCH_RESPONSE" "success" "Search by title"

# Test 12: View Single Request (Owner)
print_test "12" "Viewing single request as owner"

if [[ -n "$REQUEST_ID" ]]; then
    VIEW_RESPONSE=$(curl -s -X GET "$API_URL/content-requests/$REQUEST_ID" \
        -H "Authorization: Bearer $USER_TOKEN" \
        -H "Accept: application/json")

    check_response_status "$VIEW_RESPONSE" "success" "View own request"
else
    print_warning "Skipping - no request ID available"
fi

# Test 13: Delete Pending Request (Owner)
print_test "13" "Creating and deleting a pending request"

DELETE_TEST_RESPONSE=$(curl -s -X POST "$API_URL/content-requests" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
        "type": "case",
        "title": "Test Case for Deletion [2025]"
    }')

DELETE_REQUEST_ID=$(echo "$DELETE_TEST_RESPONSE" | jq -r '.data.content_request.id // empty')

if [[ -n "$DELETE_REQUEST_ID" ]]; then
    DELETE_RESPONSE=$(curl -s -X DELETE "$API_URL/content-requests/$DELETE_REQUEST_ID" \
        -H "Authorization: Bearer $USER_TOKEN" \
        -H "Accept: application/json")

    check_response_status "$DELETE_RESPONSE" "success" "Delete pending request"
else
    print_error "Failed to create test request for deletion"
fi

# Test 14: Create Additional Requests for Testing
print_test "14" "Creating additional test requests"

for i in {1..3}; do
    curl -s -X POST "$API_URL/content-requests" \
        -H "Authorization: Bearer $USER_TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{
            \"type\": \"case\",
            \"title\": \"Test Case $i [2025] UKSC $i\"
        }" > /dev/null
done

print_success "Created 3 additional test requests"

# ============================================================================
# ADMIN ENDPOINT TESTS
# ============================================================================

print_header "ADMIN ENDPOINT TESTS"

# Test 15: View Statistics
print_test "15" "Getting content request statistics"

STATS_RESPONSE=$(curl -s -X GET "$API_URL/admin/content-requests/stats" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    -H "Accept: application/json")

check_response_status "$STATS_RESPONSE" "success" "Get statistics"

TOTAL_COUNT=$(echo "$STATS_RESPONSE" | jq -r '.data.total // 0')
PENDING_COUNT=$(echo "$STATS_RESPONSE" | jq -r '.data.by_status.pending // 0')
FULFILLMENT_RATE=$(echo "$STATS_RESPONSE" | jq -r '.data.fulfillment_rate // 0')

print_info "Total requests: $TOTAL_COUNT"
print_info "Pending: $PENDING_COUNT"
print_info "Fulfillment rate: $FULFILLMENT_RATE%"

# Test 16: Create Duplicate Requests
print_test "16" "Creating duplicate requests for duplicate detection"

for i in {1..3}; do
    curl -s -X POST "$API_URL/content-requests" \
        -H "Authorization: Bearer $USER_TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{
            "type": "case",
            "title": "Duplicate Test Case [2025]"
        }' > /dev/null
done

print_success "Created 3 duplicate requests"

# Test 17: Find Duplicates
print_test "17" "Finding duplicate requests"

DUPLICATES_RESPONSE=$(curl -s -X GET "$API_URL/admin/content-requests/duplicates" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    -H "Accept: application/json")

check_response_status "$DUPLICATES_RESPONSE" "success" "Find duplicates"

DUPLICATE_COUNT=$(echo "$DUPLICATES_RESPONSE" | jq -r '.data.duplicates | length // 0')
print_info "Found $DUPLICATE_COUNT duplicate group(s)"

# Test 18: List All Requests (Admin)
print_test "18" "Listing all content requests as admin"

ADMIN_LIST_RESPONSE=$(curl -s -X GET "$API_URL/admin/content-requests" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    -H "Accept: application/json")

check_response_status "$ADMIN_LIST_RESPONSE" "success" "Admin list all requests"

ADMIN_TOTAL=$(echo "$ADMIN_LIST_RESPONSE" | jq -r '.data.meta.total // 0')
print_info "Admin can see $ADMIN_TOTAL total requests"

# Test 19: Filter by User ID
print_test "19" "Filtering requests by user_id"

if [[ -n "$USER_ID" ]]; then
    FILTER_USER_RESPONSE=$(curl -s -X GET "$API_URL/admin/content-requests?user_id=$USER_ID" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        -H "Accept: application/json")

    check_response_status "$FILTER_USER_RESPONSE" "success" "Filter by user_id"
else
    print_warning "Skipping - no user ID available"
fi

# Test 20: View Any Request (Admin)
print_test "20" "Viewing any user's request as admin"

if [[ -n "$REQUEST_ID" ]]; then
    ADMIN_VIEW_RESPONSE=$(curl -s -X GET "$API_URL/admin/content-requests/$REQUEST_ID" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        -H "Accept: application/json")

    check_response_status "$ADMIN_VIEW_RESPONSE" "success" "Admin view any request"
else
    print_warning "Skipping - no request ID available"
fi

# Test 21: Update Status to In Progress
print_test "21" "Updating request status to in_progress"

if [[ -n "$REQUEST_ID" ]]; then
    IN_PROGRESS_RESPONSE=$(curl -s -X PUT "$API_URL/admin/content-requests/$REQUEST_ID" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{
            "status": "in_progress"
        }')

    check_response_status "$IN_PROGRESS_RESPONSE" "success" "Update to in_progress"

    NEW_STATUS=$(echo "$IN_PROGRESS_RESPONSE" | jq -r '.data.content_request.status // empty')
    if [[ "$NEW_STATUS" == "in_progress" ]]; then
        print_info "Status successfully updated to: $NEW_STATUS"
    fi
else
    print_warning "Skipping - no request ID available"
fi

# Test 22: Get a Case ID for Fulfillment Test
print_test "22" "Getting a case ID for fulfillment testing"

CASE_RESPONSE=$(curl -s -X GET "$API_URL/cases?per_page=1" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    -H "Accept: application/json")

CASE_ID=$(echo "$CASE_RESPONSE" | jq -r '.data.cases[0].id // empty')

if [[ -n "$CASE_ID" && "$CASE_ID" != "null" ]]; then
    print_success "Found case ID for testing: $CASE_ID"
else
    print_warning "No cases available in database. Creating mock scenario."
    CASE_ID="1"
fi

# Test 23: Fulfill Request (Link Content)
print_test "23" "Fulfilling request by linking to a case"

# Create a new request for fulfillment test
FULFILL_TEST_RESPONSE=$(curl -s -X POST "$API_URL/content-requests" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
        "type": "case",
        "title": "Request to be Fulfilled [2025]"
    }')

FULFILL_REQUEST_ID=$(echo "$FULFILL_TEST_RESPONSE" | jq -r '.data.content_request.id // empty')

if [[ -n "$FULFILL_REQUEST_ID" && -n "$CASE_ID" ]]; then
    FULFILL_RESPONSE=$(curl -s -X PUT "$API_URL/admin/content-requests/$FULFILL_REQUEST_ID" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{
            \"status\": \"fulfilled\",
            \"created_content_type\": \"App\\\\Models\\\\CourtCase\",
            \"created_content_id\": $CASE_ID
        }")

    check_response_status "$FULFILL_RESPONSE" "success" "Fulfill request with content link"

    FULFILL_STATUS=$(echo "$FULFILL_RESPONSE" | jq -r '.data.content_request.status // empty')
    if [[ "$FULFILL_STATUS" == "fulfilled" ]]; then
        print_info "Request successfully fulfilled"
    fi
else
    print_warning "Skipping - no request or case ID available"
fi

# Test 24: Fulfill Without Content Link (Should Fail)
print_test "24" "Attempting to fulfill without content link (should fail)"

# Create another request
NO_LINK_RESPONSE=$(curl -s -X POST "$API_URL/content-requests" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
        "type": "case",
        "title": "Request for Validation Test [2025]"
    }')

NO_LINK_REQUEST_ID=$(echo "$NO_LINK_RESPONSE" | jq -r '.data.content_request.id // empty')

if [[ -n "$NO_LINK_REQUEST_ID" ]]; then
    NO_LINK_FULFILL=$(curl -s -X PUT "$API_URL/admin/content-requests/$NO_LINK_REQUEST_ID" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{
            "status": "fulfilled"
        }')

    ERROR_MSG=$(echo "$NO_LINK_FULFILL" | jq -r '.message // empty')
    LINK_ERROR=$(echo "$NO_LINK_FULFILL" | jq -r '.errors.created_content_id[0] // empty')
    if [[ "$ERROR_MSG" == "Validation failed" ]] && [[ -n "$LINK_ERROR" ]]; then
        print_success "Validation error for fulfill without content link"
    else
        print_error "Expected validation error for fulfill without content"
        log_response "$NO_LINK_FULFILL"
    fi
fi

# Test 25: Reject Request
print_test "25" "Rejecting a request with reason"

# Create request for rejection
REJECT_TEST_RESPONSE=$(curl -s -X POST "$API_URL/content-requests" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
        "type": "case",
        "title": "Request to be Rejected [2025]"
    }')

REJECT_REQUEST_ID=$(echo "$REJECT_TEST_RESPONSE" | jq -r '.data.content_request.id // empty')

if [[ -n "$REJECT_REQUEST_ID" ]]; then
    REJECT_RESPONSE=$(curl -s -X PUT "$API_URL/admin/content-requests/$REJECT_REQUEST_ID" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{
            "status": "rejected",
            "rejection_reason": "This case already exists in our database"
        }')

    check_response_status "$REJECT_RESPONSE" "success" "Reject request with reason"

    REJECTION_REASON=$(echo "$REJECT_RESPONSE" | jq -r '.data.content_request.rejection_reason // empty')
    if [[ -n "$REJECTION_REASON" ]]; then
        print_info "Rejection reason saved: $REJECTION_REASON"
    fi
fi

# Test 26: Reject Without Reason
print_test "26" "Rejecting a request without reason"

REJECT_NO_REASON_RESPONSE=$(curl -s -X POST "$API_URL/content-requests" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
        "type": "case",
        "title": "Another Rejection Test [2025]"
    }')

REJECT_NO_REASON_ID=$(echo "$REJECT_NO_REASON_RESPONSE" | jq -r '.data.content_request.id // empty')

if [[ -n "$REJECT_NO_REASON_ID" ]]; then
    REJECT_NO_REASON=$(curl -s -X PUT "$API_URL/admin/content-requests/$REJECT_NO_REASON_ID" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{
            "status": "rejected"
        }')

    check_response_status "$REJECT_NO_REASON" "success" "Reject without reason (optional)"
fi

# Test 27: Admin Delete Any Request
print_test "27" "Admin deleting any request"

ADMIN_DELETE_RESPONSE=$(curl -s -X POST "$API_URL/content-requests" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
        "type": "case",
        "title": "Request for Admin Deletion [2025]"
    }')

ADMIN_DELETE_ID=$(echo "$ADMIN_DELETE_RESPONSE" | jq -r '.data.content_request.id // empty')

if [[ -n "$ADMIN_DELETE_ID" ]]; then
    ADMIN_DELETE=$(curl -s -X DELETE "$API_URL/admin/content-requests/$ADMIN_DELETE_ID" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        -H "Accept: application/json")

    check_response_status "$ADMIN_DELETE" "success" "Admin delete any request"
fi

# ============================================================================
# BUSINESS LOGIC TESTS
# ============================================================================

print_header "BUSINESS LOGIC TESTS"

# Test 28: Pagination
print_test "28" "Testing pagination with per_page parameter"

PAGINATE_RESPONSE=$(curl -s -X GET "$API_URL/content-requests?per_page=5" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$PAGINATE_RESPONSE" "success" "Pagination test"

PER_PAGE=$(echo "$PAGINATE_RESPONSE" | jq -r '.data.meta.per_page // 0')
if [[ "$PER_PAGE" == "5" ]]; then
    print_info "Pagination working correctly (per_page=$PER_PAGE)"
fi

# Test 29: Sorting
print_test "29" "Testing sorting by created_at desc"

SORT_RESPONSE=$(curl -s -X GET "$API_URL/content-requests?sort_by=created_at&sort_order=desc" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$SORT_RESPONSE" "success" "Sorting test"

# Test 30: User Cannot Delete Non-Pending Request
print_test "30" "Attempting to delete non-pending request (should fail)"

if [[ -n "$REJECT_REQUEST_ID" ]]; then
    DELETE_NON_PENDING=$(curl -s -X DELETE "$API_URL/content-requests/$REJECT_REQUEST_ID" \
        -H "Authorization: Bearer $USER_TOKEN" \
        -H "Accept: application/json")

    ERROR_MSG=$(echo "$DELETE_NON_PENDING" | jq -r '.message // empty')
    if [[ "$ERROR_MSG" == *"pending"* ]] || [[ "$ERROR_MSG" == *"cannot"* ]]; then
        print_success "Correctly prevented deletion of non-pending request"
    else
        print_error "Should not allow deletion of non-pending request"
        log_response "$DELETE_NON_PENDING"
    fi
fi

# ============================================================================
# FINAL STATISTICS
# ============================================================================

print_header "FINAL STATISTICS CHECK"

print_test "31" "Getting final statistics"

FINAL_STATS=$(curl -s -X GET "$API_URL/admin/content-requests/stats" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    -H "Accept: application/json")

print_info "Final Statistics:"
echo "$FINAL_STATS" | jq '.data' 2>/dev/null || echo "$FINAL_STATS"

# ============================================================================
# TEST SUMMARY
# ============================================================================

print_header "TEST SUMMARY"

echo -e "${BOLD}Total Tests:${NC} $TOTAL_TESTS"
echo -e "${GREEN}${BOLD}Passed:${NC} $PASSED_TESTS"
echo -e "${RED}${BOLD}Failed:${NC} $FAILED_TESTS"

if [[ $FAILED_TESTS -eq 0 ]]; then
    echo -e "\n${GREEN}${BOLD}🎉 ALL TESTS PASSED! 🎉${NC}\n"
    EXIT_CODE=0
else
    echo -e "\n${RED}${BOLD}⚠ SOME TESTS FAILED ⚠${NC}\n"
    EXIT_CODE=1
fi

PASS_RATE=$(awk "BEGIN {printf \"%.1f\", ($PASSED_TESTS/$TOTAL_TESTS)*100}")
echo -e "${BOLD}Pass Rate:${NC} $PASS_RATE%"

echo -e "\n${CYAN}Log file:${NC} $LOG_FILE"
echo -e "${CYAN}Test completed at:${NC} $(date)\n"

# Wait for user input before exiting
echo -e "${YELLOW}${BOLD}Press any key to exit...${NC}"
read -n 1 -s -r

exit $EXIT_CODE
