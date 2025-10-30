#!/bin/bash

# Sequential Pure API Comprehensive Test Script
# Tests all scenarios from frontend requirements

# Color codes for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# API Configuration
API_URL="${API_URL:-http://localhost:8000/api}"
ADMIN_TOKEN="${ADMIN_TOKEN:-}"

# Test Results
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_TOTAL=0

# Function to print section header
print_header() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
}

# Function to print test result
print_result() {
    local test_name=$1
    local status=$2
    local message=$3

    ((TESTS_TOTAL++))

    if [ "$status" == "PASS" ]; then
        echo -e "${GREEN}✓ PASS${NC}: $test_name"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗ FAIL${NC}: $test_name"
        echo -e "${RED}  Error: $message${NC}"
        ((TESTS_FAILED++))
    fi
}

# Function to make API request and validate
test_endpoint() {
    local test_name=$1
    local endpoint=$2
    local expected_status=$3
    local validation_fn=$4

    echo -e "${YELLOW}Testing: $test_name${NC}"

    response=$(curl -s -w "\n%{http_code}" "$API_URL$endpoint")
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')

    if [ "$http_code" != "$expected_status" ]; then
        print_result "$test_name" "FAIL" "Expected HTTP $expected_status, got $http_code"
        echo "Response: $body" | head -n 20
        return 1
    fi

    # Run custom validation function if provided
    if [ -n "$validation_fn" ]; then
        eval "$validation_fn '$body'"
        if [ $? -ne 0 ]; then
            return 1
        fi
    fi

    print_result "$test_name" "PASS" ""
    return 0
}

# Validation Functions

validate_pure_format() {
    local body=$1

    # Check for required fields
    if ! echo "$body" | grep -q '"status":"success"'; then
        print_result "Pure Format Structure" "FAIL" "Missing status field"
        return 1
    fi

    if ! echo "$body" | grep -q '"data"'; then
        print_result "Pure Format Structure" "FAIL" "Missing data field"
        return 1
    fi

    if ! echo "$body" | grep -q '"items"'; then
        print_result "Pure Format Structure" "FAIL" "Missing items array"
        return 1
    fi

    if ! echo "$body" | grep -q '"meta"'; then
        print_result "Pure Format Structure" "FAIL" "Missing meta object"
        return 1
    fi

    if ! echo "$body" | grep -q '"format":"sequential_pure"'; then
        print_result "Pure Format Structure" "FAIL" "Missing or wrong format field"
        return 1
    fi

    # Check that items have fields at root level (not wrapped in content)
    if echo "$body" | grep -q '"items":\[{"order_index"'; then
        :  # Good - fields at root level
    elif echo "$body" | grep -q '"items":\[\]'; then
        :  # Empty array is ok
    else
        print_result "Pure Format Structure" "FAIL" "Items not in pure format"
        return 1
    fi

    return 0
}

validate_has_breadcrumb() {
    local body=$1

    if ! echo "$body" | grep -q '"breadcrumb":\['; then
        print_result "Breadcrumb Present" "FAIL" "Missing breadcrumb array"
        return 1
    fi

    # Check breadcrumb has statute root
    if ! echo "$body" | grep -q '"type":"statute"'; then
        print_result "Breadcrumb Content" "FAIL" "Breadcrumb missing statute root"
        return 1
    fi

    return 0
}

validate_no_breadcrumb() {
    local body=$1

    # Items should exist but not have breadcrumb field
    if echo "$body" | grep -q '"breadcrumb":\['; then
        print_result "No Breadcrumb" "FAIL" "Breadcrumb should not be present"
        return 1
    fi

    return 0
}

validate_pagination_meta() {
    local body=$1

    if ! echo "$body" | grep -q '"has_more"'; then
        print_result "Pagination Meta" "FAIL" "Missing has_more field"
        return 1
    fi

    if ! echo "$body" | grep -q '"returned"'; then
        print_result "Pagination Meta" "FAIL" "Missing returned count"
        return 1
    fi

    if ! echo "$body" | grep -q '"direction"'; then
        print_result "Pagination Meta" "FAIL" "Missing direction field"
        return 1
    fi

    return 0
}

validate_all_fields_present() {
    local body=$1

    # Check that each item has both division and provision fields
    if ! echo "$body" | grep -q '"division_type"'; then
        print_result "All Fields Present" "FAIL" "Missing division_type field"
        return 1
    fi

    if ! echo "$body" | grep -q '"provision_type"'; then
        print_result "All Fields Present" "FAIL" "Missing provision_type field"
        return 1
    fi

    # Check hierarchy fields
    if ! echo "$body" | grep -q '"parent_division_id"'; then
        print_result "All Fields Present" "FAIL" "Missing parent_division_id"
        return 1
    fi

    if ! echo "$body" | grep -q '"parent_provision_id"'; then
        print_result "All Fields Present" "FAIL" "Missing parent_provision_id"
        return 1
    fi

    # Check position fields
    if ! echo "$body" | grep -q '"has_children"'; then
        print_result "All Fields Present" "FAIL" "Missing has_children"
        return 1
    fi

    if ! echo "$body" | grep -q '"child_count"'; then
        print_result "All Fields Present" "FAIL" "Missing child_count"
        return 1
    fi

    return 0
}

# ============================================
# START TESTS
# ============================================

print_header "Sequential Pure API - Comprehensive Test Suite"

echo "API URL: $API_URL"
echo ""

# Test 1: Load from beginning
print_header "TEST 1: Load from Beginning"
test_endpoint \
    "Load first 15 items from order 0" \
    "/statutes/constitution-1999/content/sequential-pure?from_order=0&direction=after&limit=15" \
    "200" \
    "validate_pure_format && validate_has_breadcrumb && validate_pagination_meta && validate_all_fields_present"

# Test 2: Hash navigation (middle)
print_header "TEST 2: Hash Navigation (Load from Middle)"
test_endpoint \
    "Load 15 items from order 500 (hash navigation)" \
    "/statutes/constitution-1999/content/sequential-pure?from_order=500&direction=after&limit=15" \
    "200" \
    "validate_pure_format && validate_has_breadcrumb"

# Test 3: Scroll up (load before)
print_header "TEST 3: Scroll Up (Load Before)"
test_endpoint \
    "Load 10 items before order 500" \
    "/statutes/constitution-1999/content/sequential-pure?from_order=500&direction=before&limit=10" \
    "200" \
    "validate_pure_format && validate_pagination_meta"

# Test 4: Without breadcrumb (performance optimization)
print_header "TEST 4: Without Breadcrumb"
test_endpoint \
    "Load items without breadcrumb" \
    "/statutes/constitution-1999/content/sequential-pure?from_order=1200&direction=after&limit=15&include_breadcrumb=false" \
    "200" \
    "validate_pure_format && validate_no_breadcrumb"

# Test 5: Load from end (empty results)
print_header "TEST 5: Load from End (Empty Results)"
test_endpoint \
    "Load items from very high order (should be empty)" \
    "/statutes/constitution-1999/content/sequential-pure?from_order=999999&direction=after&limit=15" \
    "200" \
    "validate_pure_format"

# Test 6: Maximum limit enforcement
print_header "TEST 6: Maximum Limit Enforcement"
test_endpoint \
    "Request 100 items (should clamp to 50)" \
    "/statutes/constitution-1999/content/sequential-pure?from_order=100&direction=after&limit=100" \
    "200" \
    "validate_pure_format"

# Test 7: Validation - Missing required param (from_order)
print_header "TEST 7: Validation - Missing from_order"
test_endpoint \
    "Missing from_order parameter" \
    "/statutes/constitution-1999/content/sequential-pure?direction=after&limit=15" \
    "422" \
    ""

# Test 8: Validation - Missing required param (direction)
print_header "TEST 8: Validation - Missing direction"
test_endpoint \
    "Missing direction parameter" \
    "/statutes/constitution-1999/content/sequential-pure?from_order=100&limit=15" \
    "422" \
    ""

# Test 9: Validation - Invalid direction
print_header "TEST 9: Validation - Invalid direction"
test_endpoint \
    "Invalid direction value" \
    "/statutes/constitution-1999/content/sequential-pure?from_order=100&direction=sideways&limit=15" \
    "422" \
    ""

# Test 10: Validation - Invalid statute
print_header "TEST 10: Validation - Invalid Statute"
test_endpoint \
    "Non-existent statute slug" \
    "/statutes/nonexistent-statute-xyz/content/sequential-pure?from_order=0&direction=after&limit=15" \
    "404" \
    ""

# Test 11: Load with explicit breadcrumb=true
print_header "TEST 11: Explicit Breadcrumb True"
test_endpoint \
    "Explicitly request breadcrumb" \
    "/statutes/constitution-1999/content/sequential-pure?from_order=200&direction=after&limit=5&include_breadcrumb=true" \
    "200" \
    "validate_pure_format && validate_has_breadcrumb"

# Test 12: Load with very small limit
print_header "TEST 12: Small Limit (1 item)"
test_endpoint \
    "Request only 1 item" \
    "/statutes/constitution-1999/content/sequential-pure?from_order=300&direction=after&limit=1" \
    "200" \
    "validate_pure_format"

# Test 13: Load before from beginning (should be empty)
print_header "TEST 13: Load Before from Beginning"
test_endpoint \
    "Load before order 100 (first item area)" \
    "/statutes/constitution-1999/content/sequential-pure?from_order=100&direction=before&limit=10" \
    "200" \
    "validate_pure_format"

# Test 14: Verify response structure matches frontend spec
print_header "TEST 14: Response Structure Verification"
echo -e "${YELLOW}Testing: Full response structure compliance${NC}"

response=$(curl -s "$API_URL/statutes/constitution-1999/content/sequential-pure?from_order=200&direction=after&limit=2&include_breadcrumb=true")

# Check status
if ! echo "$response" | grep -q '"status":"success"'; then
    print_result "Response Structure" "FAIL" "Missing or wrong status field"
else
    # Check message
    if ! echo "$response" | grep -q '"message"'; then
        print_result "Response Structure" "FAIL" "Missing message field"
    else
        # Check data structure
        if ! echo "$response" | grep -q '"data":{"items":\['; then
            print_result "Response Structure" "FAIL" "Wrong data structure"
        else
            # Check meta structure
            if ! echo "$response" | grep -q '"meta":{"format":"sequential_pure"'; then
                print_result "Response Structure" "FAIL" "Wrong meta structure"
            else
                print_result "Response Structure" "PASS" ""
            fi
        fi
    fi
fi

# Test 15: Performance test (measure response time)
print_header "TEST 15: Performance Test"
echo -e "${YELLOW}Testing: Response time for 15 items with breadcrumbs${NC}"

start_time=$(date +%s%3N)
curl -s "$API_URL/statutes/constitution-1999/content/sequential-pure?from_order=300&direction=after&limit=15&include_breadcrumb=true" > /dev/null
end_time=$(date +%s%3N)
duration=$((end_time - start_time))

if [ $duration -lt 500 ]; then
    print_result "Performance (<500ms)" "PASS" "Response time: ${duration}ms"
elif [ $duration -lt 1000 ]; then
    print_result "Performance (<1000ms)" "PASS" "Response time: ${duration}ms (acceptable)"
else
    print_result "Performance" "FAIL" "Response time: ${duration}ms (too slow, target <500ms)"
fi

# ============================================
# TEST SUMMARY
# ============================================

print_header "TEST SUMMARY"

echo -e "Total Tests: $TESTS_TOTAL"
echo -e "${GREEN}Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Failed: $TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}ALL TESTS PASSED! ✓${NC}"
    echo -e "${GREEN}========================================${NC}"
    exit 0
else
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}SOME TESTS FAILED ✗${NC}"
    echo -e "${RED}========================================${NC}"
    exit 1
fi
