#!/bin/bash

# Case Filtering API Test Script
# Tests the new filtering functionality for /api/cases endpoint

# Configuration
API_URL="http://127.0.0.1:8000/api"
TEST_USER_TOKEN="466|XjMKhH0emA79cym1vBdDmtQAA4fjxZSA9JrC8Rtz58a64e90"
USER_AGENT="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_test() {
    echo -e "${YELLOW}[TEST]${NC} $1"
}

# Make API request and display results
make_request() {
    local url="$1"
    local description="$2"

    log_test "Testing: $description"
    echo "URL: $url"
    echo "----------------------------------------"

    response=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $TEST_USER_TOKEN" \
        -H "User-Agent: $USER_AGENT" \
        "$url")

    http_code=$(echo "$response" | grep -o 'HTTP_CODE:[0-9]*' | cut -d: -f2)
    body=$(echo "$response" | sed -e 's/HTTP_CODE:[0-9]*$//')

    echo "HTTP Status: $http_code"

    if [ "$http_code" = "200" ]; then
        echo "Response:"
        echo "$body" | python3 -m json.tool 2>/dev/null || echo "$body"
        log_success "Request successful"
    else
        echo "Response:"
        echo "$body"
        log_error "Request failed with HTTP $http_code"
    fi

    echo "========================================"
    echo ""

    return $([ "$http_code" = "200" ]; echo $?)
}

# Count cases in response
count_cases() {
    local response="$1"
    echo "$response" | python3 -c "
import json, sys
try:
    data = json.load(sys.stdin)
    if 'data' in data and 'cases' in data['data']:
        print(len(data['data']['cases']))
    elif 'data' in data and isinstance(data['data'], list):
        print(len(data['data']))
    else:
        print(0)
except:
    print(0)
" 2>/dev/null || echo "0"
}

echo "=================================================="
echo "🧪 CASE FILTERING API TEST SUITE"
echo "=================================================="
echo ""

# Test 1: Basic cases endpoint (no filters)
make_request "$API_URL/cases?per_page=3" "Basic cases list (no filters)"

# Test 2: Tag filter - criminal law
make_request "$API_URL/cases?tag=criminal&per_page=5" "Tag filter: criminal"

# Test 3: Tag filter - Damages
make_request "$API_URL/cases?tag=Damages&per_page=5" "Tag filter: Damages"

# Test 4: Course filter - Land Law
make_request "$API_URL/cases?course=Land Law&per_page=5" "Course filter: Land Law"

# Test 5: Course filter - Law of Torts
make_request "$API_URL/cases?course=Law of Torts&per_page=5" "Course filter: Law of Torts"

# Test 6: Topic filter - Tort of Deceit
make_request "$API_URL/cases?topic=Tort of Deceit&per_page=5" "Topic filter: Tort of Deceit"

# Test 7: Topic filter - Property Rights
make_request "$API_URL/cases?topic=Property Rights&per_page=5" "Topic filter: Property Rights"

# Test 8: Combined filter - tag and course
make_request "$API_URL/cases?tag=Damages&course=Law of Torts&per_page=5" "Combined: tag=Damages + course=Law of Torts"

# Test 9: Combined filter - tag, course and topic
make_request "$API_URL/cases?tag=Freedom&course=Law of Torts&topic=Privacy&per_page=5" "Combined: tag + course + topic"

# Test 10: Test with spaces in filter values
make_request "$API_URL/cases?tag=Non-Standard Products&per_page=5" "Tag with spaces: Non-Standard Products"

# Test 11: Case insensitive test
make_request "$API_URL/cases?course=land law&per_page=5" "Case insensitive: 'land law' (lowercase)"

# Test 12: Test with search + filter combination
make_request "$API_URL/cases?search=Harper&tag=Damages&per_page=5" "Search + filter: search='Harper' + tag='Damages'"

# Test 13: Test pagination with filters
make_request "$API_URL/cases?course=Law of Torts&per_page=2&page=1" "Pagination with filter: course=Law of Torts, page=1"

# Test 14: Empty results test (unlikely filter)
make_request "$API_URL/cases?course=NonExistentCourse&per_page=5" "Empty results: NonExistentCourse"

# Test 15: Authentication test (no token)
log_test "Testing: Authentication (no token)"
echo "URL: $API_URL/cases?per_page=3"
echo "----------------------------------------"

auth_response=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
    -H "Content-Type: application/json" \
    -H "User-Agent: $USER_AGENT" \
    "$API_URL/cases?per_page=3")

auth_http_code=$(echo "$auth_response" | grep -o 'HTTP_CODE:[0-9]*' | cut -d: -f2)
auth_body=$(echo "$auth_response" | sed -e 's/HTTP_CODE:[0-9]*$//')

echo "HTTP Status: $auth_http_code"
echo "Response:"
echo "$auth_body"

if [ "$auth_http_code" = "401" ]; then
    log_success "Authentication properly required"
else
    log_error "Authentication should have returned 401, got $auth_http_code"
fi

echo "========================================"
echo ""

echo "=================================================="
echo "✅ CASE FILTERING API TEST SUITE COMPLETED"
echo "=================================================="
echo ""
echo "Summary of tested functionality:"
echo "• Tag filtering (exact match and LIKE queries)"
echo "• Course filtering (exact match)"
echo "• Topic filtering (exact match)"
echo "• Combined filters (multiple parameters)"
echo "• Case sensitivity handling"
echo "• Pagination with filters"
echo "• Search + filter combinations"
echo "• Authentication requirements"
echo "• Empty result handling"
echo ""
echo "All tests completed. Check results above for any failures."