#!/bin/bash

# Feedback System Comprehensive Test Script
# Tests all user and admin feedback endpoints

# Configuration
API_URL="${API_URL:-http://localhost:8000/api}"
ADMIN_TOKEN="${ADMIN_TOKEN}"
USER_TOKEN="${USER_TOKEN}"
UNVERIFIED_USER_TOKEN="${UNVERIFIED_USER_TOKEN}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counters
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_TOTAL=0

# Log file
LOG_FILE="test-feedback-$(date +%Y%m%d-%H%M%S).log"

# Helper function to log messages
log() {
    echo -e "$1" | tee -a "$LOG_FILE"
}

# Helper function to make API calls
api_call() {
    local method=$1
    local endpoint=$2
    local token=$3
    local data=$4
    local content_type=${5:-"application/json"}

    if [ -n "$data" ]; then
        if [ "$content_type" = "application/json" ]; then
            curl -s -X "$method" "${API_URL}${endpoint}" \
                -H "Authorization: Bearer $token" \
                -H "Accept: application/json" \
                -H "Content-Type: application/json" \
                -d "$data"
        else
            # For multipart/form-data
            curl -s -X "$method" "${API_URL}${endpoint}" \
                -H "Authorization: Bearer $token" \
                -H "Accept: application/json" \
                $data
        fi
    else
        curl -s -X "$method" "${API_URL}${endpoint}" \
            -H "Authorization: Bearer $token" \
            -H "Accept: application/json"
    fi
}

# Helper function to check test result
check_result() {
    local test_name=$1
    local response=$2
    local expected_status=$3
    local check_field=$4

    TESTS_TOTAL=$((TESTS_TOTAL + 1))

    local status=$(echo "$response" | grep -o '"status":"[^"]*"' | head -1 | cut -d'"' -f4)

    if [ "$status" = "$expected_status" ]; then
        if [ -n "$check_field" ]; then
            if echo "$response" | grep -q "$check_field"; then
                log "${GREEN}✓ PASS${NC}: $test_name"
                TESTS_PASSED=$((TESTS_PASSED + 1))
                return 0
            else
                log "${RED}✗ FAIL${NC}: $test_name (field check failed)"
                log "${YELLOW}Response: $response${NC}"
                TESTS_FAILED=$((TESTS_FAILED + 1))
                return 1
            fi
        else
            log "${GREEN}✓ PASS${NC}: $test_name"
            TESTS_PASSED=$((TESTS_PASSED + 1))
            return 0
        fi
    else
        log "${RED}✗ FAIL${NC}: $test_name (expected status: $expected_status, got: $status)"
        log "${YELLOW}Response: $response${NC}"
        TESTS_FAILED=$((TESTS_FAILED + 1))
        return 1
    fi
}

# Helper function to extract feedback ID from response
extract_feedback_id() {
    local response=$1
    echo "$response" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2
}

# Start tests
log "${BLUE}================================================${NC}"
log "${BLUE}   Feedback System Comprehensive Test Suite${NC}"
log "${BLUE}================================================${NC}"
log ""
log "API URL: $API_URL"
log "Test started at: $(date)"
log ""

# Check if tokens are set
if [ -z "$ADMIN_TOKEN" ] || [ -z "$USER_TOKEN" ]; then
    log "${RED}ERROR: ADMIN_TOKEN and USER_TOKEN must be set${NC}"
    log "Usage: ADMIN_TOKEN='your_admin_token' USER_TOKEN='your_user_token' bash $0"
    exit 1
fi

# Store created feedback IDs for cleanup
CREATED_FEEDBACK_IDS=()

log "${BLUE}=== USER ENDPOINTS TESTS ===${NC}"
log ""

# Test 1: Submit feedback with content reference
log "${YELLOW}Test 1: Submit feedback with content reference${NC}"
RESPONSE=$(api_call POST "/feedback" "$USER_TOKEN" '{
    "feedback_text": "The case title appears to have a typo on page 3. It says Smtih instead of Smith.",
    "content_type": "App\\Models\\CourtCase",
    "content_id": 1,
    "page": "3"
}')
check_result "Submit feedback with content reference" "$RESPONSE" "success" "feedback_text"
FEEDBACK_ID_1=$(extract_feedback_id "$RESPONSE")
[ -n "$FEEDBACK_ID_1" ] && CREATED_FEEDBACK_IDS+=("$FEEDBACK_ID_1")
log ""

# Test 2: Submit feedback without content reference (general page feedback)
log "${YELLOW}Test 2: Submit general page feedback (no content reference)${NC}"
RESPONSE=$(api_call POST "/feedback" "$USER_TOKEN" '{
    "feedback_text": "The mobile navigation menu does not close when clicking outside of it. This is very frustrating on mobile devices."
}')
check_result "Submit general page feedback" "$RESPONSE" "success" "feedback_text"
FEEDBACK_ID_2=$(extract_feedback_id "$RESPONSE")
[ -n "$FEEDBACK_ID_2" ] && CREATED_FEEDBACK_IDS+=("$FEEDBACK_ID_2")
log ""

# Test 3: Submit feedback - validation error (too short)
log "${YELLOW}Test 3: Submit feedback - validation error (text too short)${NC}"
RESPONSE=$(api_call POST "/feedback" "$USER_TOKEN" '{
    "feedback_text": "Short"
}')
check_result "Feedback validation - text too short" "$RESPONSE" "error" "at least 10 characters"
log ""

# Test 4: Submit feedback - validation error (missing content_id when content_type provided)
log "${YELLOW}Test 4: Submit feedback - validation error (missing content_id)${NC}"
RESPONSE=$(api_call POST "/feedback" "$USER_TOKEN" '{
    "feedback_text": "This is a test feedback with missing content_id field.",
    "content_type": "App\\Models\\CourtCase"
}')
check_result "Feedback validation - missing content_id" "$RESPONSE" "error" "Content ID"
log ""

# Test 5: Submit feedback - validation error (invalid content_type)
log "${YELLOW}Test 5: Submit feedback - validation error (invalid content_type)${NC}"
RESPONSE=$(api_call POST "/feedback" "$USER_TOKEN" '{
    "feedback_text": "This is a test feedback with invalid content type value.",
    "content_type": "App\\Models\\InvalidModel",
    "content_id": 1
}')
check_result "Feedback validation - invalid content_type" "$RESPONSE" "error"
log ""

# Test 6: Submit feedback - unauthorized (no token)
log "${YELLOW}Test 6: Submit feedback - unauthorized access (no token)${NC}"
RESPONSE=$(curl -s -X POST "${API_URL}/feedback" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{
        "feedback_text": "This should fail because no auth token is provided."
    }')
# Check for unauthenticated response (should not have "success" status)
if echo "$RESPONSE" | grep -q "Unauthenticated\|Authentication required\|error"; then
    TESTS_TOTAL=$((TESTS_TOTAL + 1))
    TESTS_PASSED=$((TESTS_PASSED + 1))
    log "${GREEN}✓ PASS${NC}: Unauthorized access rejected"
else
    TESTS_TOTAL=$((TESTS_TOTAL + 1))
    TESTS_FAILED=$((TESTS_FAILED + 1))
    log "${RED}✗ FAIL${NC}: Unauthorized access should be rejected"
    log "${YELLOW}Response: $RESPONSE${NC}"
fi
log ""

# Test 7: Get user's feedback list
log "${YELLOW}Test 7: Get user's feedback list${NC}"
RESPONSE=$(api_call GET "/feedback" "$USER_TOKEN")
check_result "Get user's feedback list" "$RESPONSE" "success" "feedback"
log ""

# Test 8: Get user's feedback list with pagination
log "${YELLOW}Test 8: Get user's feedback list with pagination${NC}"
RESPONSE=$(api_call GET "/feedback?per_page=10&page=1" "$USER_TOKEN")
check_result "Get feedback list with pagination" "$RESPONSE" "success" "meta"
log ""

# Test 9: Get user's feedback list filtered by status
log "${YELLOW}Test 9: Get user's feedback list filtered by status=pending${NC}"
RESPONSE=$(api_call GET "/feedback?status=pending" "$USER_TOKEN")
check_result "Get feedback list filtered by status" "$RESPONSE" "success" "feedback"
log ""

# Test 10: Get user's feedback list with search
log "${YELLOW}Test 10: Get user's feedback list with search${NC}"
RESPONSE=$(api_call GET "/feedback?search=typo" "$USER_TOKEN")
check_result "Get feedback list with search" "$RESPONSE" "success" "feedback"
log ""

# Test 11: Get user's feedback list sorted
log "${YELLOW}Test 11: Get user's feedback list sorted by created_at desc${NC}"
RESPONSE=$(api_call GET "/feedback?sort_by=created_at&sort_order=desc" "$USER_TOKEN")
check_result "Get feedback list sorted" "$RESPONSE" "success" "feedback"
log ""

# Test 12: Get single feedback (own)
if [ -n "$FEEDBACK_ID_1" ]; then
    log "${YELLOW}Test 12: Get single feedback (own)${NC}"
    RESPONSE=$(api_call GET "/feedback/$FEEDBACK_ID_1" "$USER_TOKEN")
    check_result "Get single feedback (own)" "$RESPONSE" "success" "feedback_text"
    log ""
fi

# Test 13: Get single feedback - non-existent
log "${YELLOW}Test 13: Get single feedback - non-existent ID${NC}"
RESPONSE=$(api_call GET "/feedback/999999" "$USER_TOKEN")
# Should return error or 404
if echo "$RESPONSE" | grep -q "error\|not found\|Not Found"; then
    TESTS_TOTAL=$((TESTS_TOTAL + 1))
    TESTS_PASSED=$((TESTS_PASSED + 1))
    log "${GREEN}✓ PASS${NC}: Non-existent feedback returns error"
else
    TESTS_TOTAL=$((TESTS_TOTAL + 1))
    TESTS_FAILED=$((TESTS_FAILED + 1))
    log "${RED}✗ FAIL${NC}: Non-existent feedback should return error"
    log "${YELLOW}Response: $RESPONSE${NC}"
fi
log ""

log "${BLUE}=== ADMIN ENDPOINTS TESTS ===${NC}"
log ""

# Test 14: Admin - Get all feedback
log "${YELLOW}Test 14: Admin - Get all feedback${NC}"
RESPONSE=$(api_call GET "/admin/feedback" "$ADMIN_TOKEN")
check_result "Admin - Get all feedback" "$RESPONSE" "success" "stats"
log ""

# Test 15: Admin - Get all feedback with filters
log "${YELLOW}Test 15: Admin - Get feedback filtered by status=pending${NC}"
RESPONSE=$(api_call GET "/admin/feedback?status=pending" "$ADMIN_TOKEN")
check_result "Admin - Get feedback with status filter" "$RESPONSE" "success" "feedback"
log ""

# Test 16: Admin - Get feedback with date range
log "${YELLOW}Test 16: Admin - Get feedback with date range filter${NC}"
TODAY=$(date +%Y-%m-%d)
RESPONSE=$(api_call GET "/admin/feedback?date_from=$TODAY&date_to=$TODAY" "$ADMIN_TOKEN")
check_result "Admin - Get feedback with date range" "$RESPONSE" "success" "feedback"
log ""

# Test 17: Admin - Get feedback with content_type filter
log "${YELLOW}Test 17: Admin - Get feedback filtered by content_type${NC}"
RESPONSE=$(api_call GET "/admin/feedback?content_type=App\\\\Models\\\\CourtCase" "$ADMIN_TOKEN")
check_result "Admin - Get feedback with content_type filter" "$RESPONSE" "success" "feedback"
log ""

# Test 18: Admin - Get feedback with search
log "${YELLOW}Test 18: Admin - Get feedback with search${NC}"
RESPONSE=$(api_call GET "/admin/feedback?search=typo" "$ADMIN_TOKEN")
check_result "Admin - Get feedback with search" "$RESPONSE" "success" "feedback"
log ""

# Test 19: Admin - Get single feedback
if [ -n "$FEEDBACK_ID_1" ]; then
    log "${YELLOW}Test 19: Admin - Get single feedback${NC}"
    RESPONSE=$(api_call GET "/admin/feedback/$FEEDBACK_ID_1" "$ADMIN_TOKEN")
    check_result "Admin - Get single feedback" "$RESPONSE" "success" "feedback_text"
    log ""
fi

# Test 20: Admin - Update feedback status to under_review
if [ -n "$FEEDBACK_ID_1" ]; then
    log "${YELLOW}Test 20: Admin - Update feedback status to under_review${NC}"
    RESPONSE=$(api_call PATCH "/admin/feedback/$FEEDBACK_ID_1/status" "$ADMIN_TOKEN" '{
        "status": "under_review"
    }')
    check_result "Admin - Update status to under_review" "$RESPONSE" "success" "under_review"
    log ""
fi

# Test 21: Admin - Update feedback status to resolved
if [ -n "$FEEDBACK_ID_1" ]; then
    log "${YELLOW}Test 21: Admin - Update feedback status to resolved${NC}"
    RESPONSE=$(api_call PATCH "/admin/feedback/$FEEDBACK_ID_1/status" "$ADMIN_TOKEN" '{
        "status": "resolved"
    }')
    check_result "Admin - Update status to resolved" "$RESPONSE" "success" "resolved"
    # Should also have resolved_by and resolved_at
    if echo "$RESPONSE" | grep -q "resolved_by"; then
        log "${GREEN}  ✓ resolved_by field present${NC}"
    else
        log "${RED}  ✗ resolved_by field missing${NC}"
    fi
    if echo "$RESPONSE" | grep -q "resolved_at"; then
        log "${GREEN}  ✓ resolved_at field present${NC}"
    else
        log "${RED}  ✗ resolved_at field missing${NC}"
    fi
    log ""
fi

# Test 22: Admin - Update feedback status - invalid status
if [ -n "$FEEDBACK_ID_1" ]; then
    log "${YELLOW}Test 22: Admin - Update feedback status with invalid value${NC}"
    RESPONSE=$(api_call PATCH "/admin/feedback/$FEEDBACK_ID_1/status" "$ADMIN_TOKEN" '{
        "status": "invalid_status"
    }')
    check_result "Admin - Update status with invalid value" "$RESPONSE" "error"
    log ""
fi

# Test 23: Admin - Move feedback to issues
if [ -n "$FEEDBACK_ID_2" ]; then
    log "${YELLOW}Test 23: Admin - Move feedback to issues${NC}"
    RESPONSE=$(api_call POST "/admin/feedback/$FEEDBACK_ID_2/move-to-issues" "$ADMIN_TOKEN")
    check_result "Admin - Move feedback to issues" "$RESPONSE" "success" "moved_to_issues"
    # Should have moved_by and moved_at
    if echo "$RESPONSE" | grep -q "moved_by"; then
        log "${GREEN}  ✓ moved_by field present${NC}"
    else
        log "${RED}  ✗ moved_by field missing${NC}"
    fi
    if echo "$RESPONSE" | grep -q "moved_at"; then
        log "${GREEN}  ✓ moved_at field present${NC}"
    else
        log "${RED}  ✗ moved_at field missing${NC}"
    fi
    log ""
fi

# Test 24: Admin - Move already moved feedback to issues (should fail)
if [ -n "$FEEDBACK_ID_2" ]; then
    log "${YELLOW}Test 24: Admin - Move already moved feedback to issues${NC}"
    RESPONSE=$(api_call POST "/admin/feedback/$FEEDBACK_ID_2/move-to-issues" "$ADMIN_TOKEN")
    check_result "Admin - Move already moved feedback" "$RESPONSE" "error" "already been moved"
    log ""
fi

# Test 25: Admin - Get feedback filtered by moved_to_issues
log "${YELLOW}Test 25: Admin - Get feedback filtered by moved_to_issues=true${NC}"
RESPONSE=$(api_call GET "/admin/feedback?moved_to_issues=true" "$ADMIN_TOKEN")
check_result "Admin - Get feedback filtered by moved_to_issues" "$RESPONSE" "success" "feedback"
log ""

# Test 26: User cannot access admin endpoints
log "${YELLOW}Test 26: User cannot access admin endpoints${NC}"
RESPONSE=$(api_call GET "/admin/feedback" "$USER_TOKEN")
if echo "$RESPONSE" | grep -q "error\|Unauthorized\|Forbidden\|forbidden"; then
    TESTS_TOTAL=$((TESTS_TOTAL + 1))
    TESTS_PASSED=$((TESTS_PASSED + 1))
    log "${GREEN}✓ PASS${NC}: User cannot access admin endpoints"
else
    TESTS_TOTAL=$((TESTS_TOTAL + 1))
    TESTS_FAILED=$((TESTS_FAILED + 1))
    log "${RED}✗ FAIL${NC}: User should not access admin endpoints"
    log "${YELLOW}Response: $RESPONSE${NC}"
fi
log ""

# Test 27: Check stats object in admin list endpoint
log "${YELLOW}Test 27: Admin - Verify stats object in list endpoint${NC}"
RESPONSE=$(api_call GET "/admin/feedback" "$ADMIN_TOKEN")
if echo "$RESPONSE" | grep -q '"stats"'; then
    if echo "$RESPONSE" | grep -q '"total"' && echo "$RESPONSE" | grep -q '"pending"' && echo "$RESPONSE" | grep -q '"under_review"' && echo "$RESPONSE" | grep -q '"resolved"' && echo "$RESPONSE" | grep -q '"moved_to_issues"'; then
        TESTS_TOTAL=$((TESTS_TOTAL + 1))
        TESTS_PASSED=$((TESTS_PASSED + 1))
        log "${GREEN}✓ PASS${NC}: Stats object contains all required fields"
    else
        TESTS_TOTAL=$((TESTS_TOTAL + 1))
        TESTS_FAILED=$((TESTS_FAILED + 1))
        log "${RED}✗ FAIL${NC}: Stats object missing required fields"
        log "${YELLOW}Response: $RESPONSE${NC}"
    fi
else
    TESTS_TOTAL=$((TESTS_TOTAL + 1))
    TESTS_FAILED=$((TESTS_FAILED + 1))
    log "${RED}✗ FAIL${NC}: Stats object not found in response"
    log "${YELLOW}Response: $RESPONSE${NC}"
fi
log ""

# Test Summary
log ""
log "${BLUE}================================================${NC}"
log "${BLUE}               TEST SUMMARY${NC}"
log "${BLUE}================================================${NC}"
log "Total Tests: $TESTS_TOTAL"
log "${GREEN}Passed: $TESTS_PASSED${NC}"
log "${RED}Failed: $TESTS_FAILED${NC}"

if [ $TESTS_FAILED -eq 0 ]; then
    log "${GREEN}All tests passed! ✓${NC}"
    exit 0
else
    log "${RED}Some tests failed. Please review the log above.${NC}"
    exit 1
fi
