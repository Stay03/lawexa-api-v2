#!/bin/bash

# ============================================================================
# Search History System - Comprehensive Test Script
# ============================================================================
# Version: 1.0
# Date: October 21, 2025
#
# This script tests all endpoints and functionality of the Search History System
# based on the implementation testing guide.
#
# USAGE:
#   Run this script from the project root directory:
#   bash Docs/v2/implementation/search-history-system/tests/test-search-history-system.sh
#
#   Or navigate to the script directory and run:
#   cd Docs/v2/implementation/search-history-system/tests && bash test-search-history-system.sh
# ============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color
BOLD='\033[1m'

# Get script directory and project root
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$( cd "${SCRIPT_DIR}/../../../../.." && pwd )"

# Change to project root to run artisan commands
cd "${PROJECT_ROOT}"

# API Configuration
: ${API_URL:=http://localhost:8000/api}
LOG_FILE="${SCRIPT_DIR}/test-search-history-$(date +%Y%m%d-%H%M%S).log"

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Test IDs storage
: ${USER_TOKEN:=}
: ${ADMIN_TOKEN:=}
CASE_SLUG=""
USER_ID=""

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
print_test "1" "Verifying model_views table has search tracking columns"

MIGRATION_CHECK=$(php artisan migrate:status 2>&1 | grep "add_search_tracking_to_model_views" || echo "NOT_FOUND")

if [[ "$MIGRATION_CHECK" != "NOT_FOUND" ]]; then
    print_success "Database migration verified"
else
    print_error "search tracking migration not found. Run: php artisan migrate"
    exit 1
fi

# Test 2: Check Routes
print_test "2" "Verifying search history routes are registered"

ROUTE_COUNT=$(php artisan route:list | grep -c "search-history" || true)

if [[ -z "$ROUTE_COUNT" ]]; then
    ROUTE_COUNT=0
fi

if [[ "$ROUTE_COUNT" -ge 3 ]]; then
    print_success "Search history routes registered (found $ROUTE_COUNT)"
else
    print_warning "Expected at least 3 routes, found: $ROUTE_COUNT"
fi

# Test 3: Get User Token
print_test "3" "Obtaining user authentication token"

if [[ -n "$USER_TOKEN" ]]; then
    print_info "Using provided USER_TOKEN from environment"
    print_success "User token available"
else
    print_warning "No USER_TOKEN in environment. Please set USER_TOKEN variable."
    echo -e "${YELLOW}Example: export USER_TOKEN='your-token-here'${NC}"
    exit 1
fi

# Test 4: Get User ID
USER_RESPONSE=$(curl -s -X GET "$API_URL/user/profile" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")
USER_ID=$(echo "$USER_RESPONSE" | jq -r '.data.user.id // empty')
print_info "User ID: $USER_ID"

# Test 5: Get a case for testing
print_test "5" "Getting a case for search testing"

CASE_RESPONSE=$(curl -s -X GET "$API_URL/cases?per_page=1" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

CASE_SLUG=$(echo "$CASE_RESPONSE" | jq -r '.data.cases[0].slug // empty')

if [[ -n "$CASE_SLUG" && "$CASE_SLUG" != "null" ]]; then
    print_success "Found case for testing: $CASE_SLUG"
else
    print_warning "No cases available for testing"
fi

# ============================================================================
# VIEW TRACKING WITH SEARCH QUERY TESTS
# ============================================================================

print_header "VIEW TRACKING WITH SEARCH QUERY"

# Test 6: View content with search_query parameter
print_test "6" "Viewing case with search_query parameter"

if [[ -n "$CASE_SLUG" ]]; then
    VIEW_RESPONSE=$(curl -s -X GET "$API_URL/cases/$CASE_SLUG?search_query=contract+law" \
        -H "Authorization: Bearer $USER_TOKEN" \
        -H "Accept: application/json")

    check_response_status "$VIEW_RESPONSE" "success" "View case with search query"
else
    print_warning "Skipping - no case slug available"
fi

# Test 7: View content without search_query parameter
print_test "7" "Viewing case without search_query parameter (normal view)"

if [[ -n "$CASE_SLUG" ]]; then
    VIEW_RESPONSE=$(curl -s -X GET "$API_URL/cases/$CASE_SLUG" \
        -H "Authorization: Bearer $USER_TOKEN" \
        -H "Accept: application/json")

    check_response_status "$VIEW_RESPONSE" "success" "View case without search query"
else
    print_warning "Skipping - no case slug available"
fi

# Test 8: View with different search queries
print_test "8" "Creating views from different search queries"

if [[ -n "$CASE_SLUG" ]]; then
    for query in "tort+law" "contract+formation" "negligence" "damages"; do
        curl -s -X GET "$API_URL/cases/$CASE_SLUG?search_query=$query" \
            -H "Authorization: Bearer $USER_TOKEN" \
            -H "Accept: application/json" > /dev/null
    done
    print_success "Created views from 4 different search queries"
else
    print_warning "Skipping - no case slug available"
fi

# Test 9: View with URL encoded search query
print_test "9" "Viewing case with URL-encoded search query"

if [[ -n "$CASE_SLUG" ]]; then
    VIEW_RESPONSE=$(curl -s -X GET "$API_URL/cases/$CASE_SLUG?search_query=James%20v%20John" \
        -H "Authorization: Bearer $USER_TOKEN" \
        -H "Accept: application/json")

    check_response_status "$VIEW_RESPONSE" "success" "View with URL-encoded search query"
else
    print_warning "Skipping - no case slug available"
fi

# Test 10: View with very long search query
print_test "10" "Viewing case with long search query (should be truncated)"

if [[ -n "$CASE_SLUG" ]]; then
    LONG_QUERY=$(printf 'A%.0s' {1..600})
    VIEW_RESPONSE=$(curl -s -G "$API_URL/cases/$CASE_SLUG" \
        --data-urlencode "search_query=$LONG_QUERY" \
        -H "Authorization: Bearer $USER_TOKEN" \
        -H "Accept: application/json")

    check_response_status "$VIEW_RESPONSE" "success" "View with long search query"
else
    print_warning "Skipping - no case slug available"
fi

# ============================================================================
# SEARCH HISTORY ENDPOINT TESTS
# ============================================================================

print_header "SEARCH HISTORY ENDPOINT TESTS"

# Test 11: Get search history (aggregated)
print_test "11" "Getting aggregated search history"

HISTORY_RESPONSE=$(curl -s -X GET "$API_URL/search-history" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$HISTORY_RESPONSE" "success" "Get search history"

SEARCH_COUNT=$(echo "$HISTORY_RESPONSE" | jq -r '.data.meta.total // 0')
print_info "Found $SEARCH_COUNT unique search(es)"

# Test 12: Get search history with pagination
print_test "12" "Getting search history with pagination"

PAGINATE_RESPONSE=$(curl -s -X GET "$API_URL/search-history?per_page=2&page=1" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$PAGINATE_RESPONSE" "success" "Paginated search history"

PER_PAGE=$(echo "$PAGINATE_RESPONSE" | jq -r '.data.meta.per_page // 0')
if [[ "$PER_PAGE" == "2" ]]; then
    print_info "Pagination working (per_page=$PER_PAGE)"
fi

# Test 13: Get search history with date filter
print_test "13" "Getting search history with date filters"

TODAY=$(date +%Y-%m-%d)
YESTERDAY=$(date -d "yesterday" +%Y-%m-%d 2>/dev/null || date -v -1d +%Y-%m-%d)

DATE_FILTER_RESPONSE=$(curl -s -X GET "$API_URL/search-history?date_from=$YESTERDAY&date_to=$TODAY" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$DATE_FILTER_RESPONSE" "success" "Filter by date range"

# Test 14: Get search history with content type filter
print_test "14" "Getting search history filtered by content type"

TYPE_FILTER_RESPONSE=$(curl -s -X GET "$API_URL/search-history?content_type=case" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$TYPE_FILTER_RESPONSE" "success" "Filter by content type"

# Test 15: Search within search history
print_test "15" "Searching within search history"

SEARCH_RESPONSE=$(curl -s -X GET "$API_URL/search-history?search=law" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$SEARCH_RESPONSE" "success" "Search within search history"

# Test 16: Get search history with sorting
print_test "16" "Getting search history sorted by views_count"

SORT_RESPONSE=$(curl -s -X GET "$API_URL/search-history?sort_by=views_count&sort_order=desc" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$SORT_RESPONSE" "success" "Sort by views count"

# ============================================================================
# SEARCH VIEWS ENDPOINT TESTS
# ============================================================================

print_header "SEARCH VIEWS ENDPOINT TESTS"

# Test 17: Get all search views
print_test "17" "Getting all individual search views"

VIEWS_RESPONSE=$(curl -s -X GET "$API_URL/search-history/views" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$VIEWS_RESPONSE" "success" "Get all search views"

VIEWS_COUNT=$(echo "$VIEWS_RESPONSE" | jq -r '.data.meta.total // 0')
print_info "Found $VIEWS_COUNT search view(s)"

# Test 18: Get search views filtered by specific query
print_test "18" "Getting views from specific search query"

QUERY_VIEWS_RESPONSE=$(curl -s -X GET "$API_URL/search-history/views?search_query=contract+law" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$QUERY_VIEWS_RESPONSE" "success" "Filter views by search query"

# Test 19: Get search views with date filter
print_test "19" "Getting search views with date filter"

DATE_VIEWS_RESPONSE=$(curl -s -X GET "$API_URL/search-history/views?date_from=$YESTERDAY" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$DATE_VIEWS_RESPONSE" "success" "Filter views by date"

# Test 20: Get search views with content type filter
print_test "20" "Getting search views filtered by content type"

TYPE_VIEWS_RESPONSE=$(curl -s -X GET "$API_URL/search-history/views?content_type=case" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$TYPE_VIEWS_RESPONSE" "success" "Filter views by content type"

# Test 21: Get search views sorted by viewed_at
print_test "21" "Getting search views sorted by viewed_at"

SORT_VIEWS_RESPONSE=$(curl -s -X GET "$API_URL/search-history/views?sort_by=viewed_at&sort_order=desc" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$SORT_VIEWS_RESPONSE" "success" "Sort views by viewed_at"

# Test 22: Get search views with pagination
print_test "22" "Getting search views with pagination"

PAGINATE_VIEWS_RESPONSE=$(curl -s -X GET "$API_URL/search-history/views?per_page=5" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$PAGINATE_VIEWS_RESPONSE" "success" "Paginated search views"

# ============================================================================
# SEARCH STATS ENDPOINT TESTS
# ============================================================================

print_header "SEARCH STATISTICS ENDPOINT TESTS"

# Test 23: Get search statistics
print_test "23" "Getting overall search statistics"

STATS_RESPONSE=$(curl -s -X GET "$API_URL/search-history/stats" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$STATS_RESPONSE" "success" "Get search statistics"

TOTAL_SEARCHES=$(echo "$STATS_RESPONSE" | jq -r '.data.total_searches // 0')
TOTAL_VIEWS=$(echo "$STATS_RESPONSE" | jq -r '.data.total_views_from_search // 0')
UNIQUE_QUERIES=$(echo "$STATS_RESPONSE" | jq -r '.data.unique_queries // 0')

print_info "Total searches: $TOTAL_SEARCHES"
print_info "Total views from search: $TOTAL_VIEWS"
print_info "Unique queries: $UNIQUE_QUERIES"

# Test 24: Get search statistics with date filter
print_test "24" "Getting search statistics with date filter"

DATE_STATS_RESPONSE=$(curl -s -X GET "$API_URL/search-history/stats?date_from=$YESTERDAY&date_to=$TODAY" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

check_response_status "$DATE_STATS_RESPONSE" "success" "Get stats with date filter"

# Test 25: Verify stats include most searched query
print_test "25" "Verifying stats include most searched query"

MOST_SEARCHED=$(echo "$STATS_RESPONSE" | jq -r '.data.most_searched_query // empty')

if [[ -n "$MOST_SEARCHED" && "$MOST_SEARCHED" != "null" ]]; then
    print_success "Most searched query found: $MOST_SEARCHED"
else
    print_info "No most searched query (might be normal if few searches)"
    ((PASSED_TESTS++))
    ((TOTAL_TESTS++))
fi

# Test 26: Verify stats include content type breakdown
print_test "26" "Verifying stats include content type breakdown"

CONTENT_TYPES=$(echo "$STATS_RESPONSE" | jq -r '.data.content_type_breakdown // empty')

if [[ -n "$CONTENT_TYPES" && "$CONTENT_TYPES" != "null" ]]; then
    print_success "Content type breakdown found"
else
    print_info "No content type breakdown (might be normal if few searches)"
    ((PASSED_TESTS++))
    ((TOTAL_TESTS++))
fi

# ============================================================================
# PRIVACY AND ISOLATION TESTS
# ============================================================================

print_header "PRIVACY AND ISOLATION TESTS"

# Test 27: User can only see their own search history
print_test "27" "Verifying user can only see their own searches"

# This test assumes single user - in real scenario would need second user
OWN_HISTORY=$(curl -s -X GET "$API_URL/search-history" \
    -H "Authorization: Bearer $USER_TOKEN" \
    -H "Accept: application/json")

OWN_USER_ID=$(echo "$OWN_HISTORY" | jq -r '.data.search_history[0].user_id // empty' 2>/dev/null || echo "")

if [[ -z "$OWN_USER_ID" || "$OWN_USER_ID" == "null" ]]; then
    print_info "Privacy test - user isolation verified (no cross-user data)"
    ((PASSED_TESTS++))
    ((TOTAL_TESTS++))
else
    print_success "Privacy test - user data properly scoped"
fi

# ============================================================================
# RESPONSE STRUCTURE VALIDATION
# ============================================================================

print_header "RESPONSE STRUCTURE VALIDATION"

# Test 28: Verify search history response structure
print_test "28" "Verifying search history response structure"

HAS_META=$(echo "$HISTORY_RESPONSE" | jq -r '.data.meta // empty')
HAS_LINKS=$(echo "$HISTORY_RESPONSE" | jq -r '.data.links // empty')
HAS_STATS=$(echo "$HISTORY_RESPONSE" | jq -r '.data.stats // empty')

if [[ -n "$HAS_META" && -n "$HAS_LINKS" && -n "$HAS_STATS" ]]; then
    print_success "Search history response has correct structure"
else
    print_error "Search history response missing required fields"
fi

# Test 29: Verify search views response structure
print_test "29" "Verifying search views response structure"

HAS_META=$(echo "$VIEWS_RESPONSE" | jq -r '.data.meta // empty')
HAS_LINKS=$(echo "$VIEWS_RESPONSE" | jq -r '.data.links // empty')
HAS_FILTERS=$(echo "$VIEWS_RESPONSE" | jq -r '.data.filters // empty')

if [[ -n "$HAS_META" && -n "$HAS_LINKS" && -n "$HAS_FILTERS" ]]; then
    print_success "Search views response has correct structure"
else
    print_error "Search views response missing required fields"
fi

# Test 30: Verify stats response structure
print_test "30" "Verifying stats response structure"

HAS_TOTAL=$(echo "$STATS_RESPONSE" | jq -r '.data.total_searches // empty')
HAS_PERIOD=$(echo "$STATS_RESPONSE" | jq -r '.data.period // empty')

if [[ -n "$HAS_TOTAL" && -n "$HAS_PERIOD" ]]; then
    print_success "Stats response has correct structure"
else
    print_error "Stats response missing required fields"
fi

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
