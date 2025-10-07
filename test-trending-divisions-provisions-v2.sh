#!/bin/bash

# Test script for trending divisions and provisions - View 2 new ones 3 times each
API_URL="https://rest.lawexa.com/api"
USER_AGENT="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"

echo "========================================================="
echo "Testing Trending: View 2 Divisions & 2 Provisions (3x)"
echo "========================================================="
echo ""

# Generate random email to avoid conflicts
TIMESTAMP=$(date +%s)
RANDOM_NUM=$RANDOM
TEST_EMAIL="test_trending_v2_${TIMESTAMP}_${RANDOM_NUM}@example.com"
TEST_PASSWORD="TestPassword123!"
TEST_NAME="Trending Tester V2"

echo "Step 1: Creating test user account..."
echo "Email: $TEST_EMAIL"
echo "--------------------------------------"

REGISTER_RESPONSE=$(curl -X POST "$API_URL/auth/register" \
  -H "Content-Type: application/json" \
  -H "User-Agent: $USER_AGENT" \
  -d "{
    \"name\": \"$TEST_NAME\",
    \"email\": \"$TEST_EMAIL\",
    \"password\": \"$TEST_PASSWORD\",
    \"password_confirmation\": \"$TEST_PASSWORD\"
  }" \
  -w "\nHTTP_STATUS:%{http_code}" \
  -s)

HTTP_STATUS=$(echo "$REGISTER_RESPONSE" | grep "HTTP_STATUS" | cut -d':' -f2)
REGISTER_DATA=$(echo "$REGISTER_RESPONSE" | sed '/HTTP_STATUS/d')

echo "$REGISTER_DATA" | head -c 500
echo ""
echo "HTTP Status: $HTTP_STATUS"
echo ""

if [ "$HTTP_STATUS" != "200" ] && [ "$HTTP_STATUS" != "201" ]; then
  echo "❌ Registration failed!"
  exit 1
fi

echo "✓ User created successfully"
echo ""

echo "Step 2: Logging in to get authentication token..."
echo "---------------------------------------------------"

LOGIN_RESPONSE=$(curl -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "User-Agent: $USER_AGENT" \
  -d "{
    \"email\": \"$TEST_EMAIL\",
    \"password\": \"$TEST_PASSWORD\"
  }" \
  -w "\nHTTP_STATUS:%{http_code}" \
  -s)

HTTP_STATUS=$(echo "$LOGIN_RESPONSE" | grep "HTTP_STATUS" | cut -d':' -f2)
LOGIN_DATA=$(echo "$LOGIN_RESPONSE" | sed '/HTTP_STATUS/d')

echo "$LOGIN_DATA" | head -c 500
echo ""
echo "HTTP Status: $HTTP_STATUS"
echo ""

if [ "$HTTP_STATUS" != "200" ]; then
  echo "❌ Login failed!"
  exit 1
fi

# Extract token
TOKEN=$(echo "$LOGIN_DATA" | grep -o '"token":"[^"]*"' | head -1 | sed 's/"token":"\(.*\)"/\1/')

if [ -z "$TOKEN" ]; then
  echo "❌ Failed to extract token!"
  exit 1
fi

echo "✓ Logged in successfully"
echo "Token: ${TOKEN:0:20}..."
echo ""

# Use a statute with divisions and provisions
STATUTE_SLUG="constitution-of-the-federal-republic-of-nigeria-1999"

echo "Step 3: Fetching divisions and provisions from $STATUTE_SLUG..."
echo "------------------------------------------------------------------"

# Get divisions list
echo "Fetching divisions..."
DIVISIONS_LIST=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG/divisions" \
  -H "Accept: application/json" \
  -H "User-Agent: $USER_AGENT" \
  -H "Authorization: Bearer $TOKEN" \
  -s)

# Extract 2 different division slugs (skip duplicates, get 4th and 5th)
DIV_SLUG_1=$(echo "$DIVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -4 | tail -1 | cut -d':' -f2 | tr -d '"')
DIV_SLUG_2=$(echo "$DIVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -5 | tail -1 | cut -d':' -f2 | tr -d '"')

# Extract division IDs for validation
DIV_ID_1=$(echo "$DIVISIONS_LIST" | grep -B2 "\"slug\":\"$DIV_SLUG_1\"" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)
DIV_ID_2=$(echo "$DIVISIONS_LIST" | grep -B2 "\"slug\":\"$DIV_SLUG_2\"" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)

echo "✓ Selected divisions:"
echo "  1. $DIV_SLUG_1 (ID: ${DIV_ID_1:-unknown})"
echo "  2. $DIV_SLUG_2 (ID: ${DIV_ID_2:-unknown})"
echo ""

# Get provisions list
echo "Fetching provisions..."
PROVISIONS_LIST=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG/provisions" \
  -H "Accept: application/json" \
  -H "User-Agent: $USER_AGENT" \
  -H "Authorization: Bearer $TOKEN" \
  -s)

# Extract 2 different provision slugs (get 4th and 5th)
PROV_SLUG_1=$(echo "$PROVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -4 | tail -1 | cut -d':' -f2 | tr -d '"')
PROV_SLUG_2=$(echo "$PROVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -5 | tail -1 | cut -d':' -f2 | tr -d '"')

# Extract provision IDs for validation
PROV_ID_1=$(echo "$PROVISIONS_LIST" | grep -B2 "\"slug\":\"$PROV_SLUG_1\"" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)
PROV_ID_2=$(echo "$PROVISIONS_LIST" | grep -B2 "\"slug\":\"$PROV_SLUG_2\"" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)

echo "✓ Selected provisions:"
echo "  1. $PROV_SLUG_1 (ID: ${PROV_ID_1:-unknown})"
echo "  2. $PROV_SLUG_2 (ID: ${PROV_ID_2:-unknown})"
echo ""

echo "Step 4: Viewing 2 divisions and 2 provisions (3 times each)..."
echo "---------------------------------------------------------------"

for i in 1 2 3; do
  echo "Round $i of 3:"
  echo ""

  # View Division 1
  if [ ! -z "$DIV_SLUG_1" ]; then
    echo "  Viewing division: $DIV_SLUG_1..."
    VIEW_DIV1=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG/divisions/$DIV_SLUG_1" \
      -H "Accept: application/json" \
      -H "User-Agent: $USER_AGENT" \
      -H "Authorization: Bearer $TOKEN" \
      -w "\nHTTP_STATUS:%{http_code}" \
      -s)
    STATUS_DIV1=$(echo "$VIEW_DIV1" | grep "HTTP_STATUS" | cut -d':' -f2)
    echo "    Status: $STATUS_DIV1"
    sleep 1
  fi

  # View Division 2
  if [ ! -z "$DIV_SLUG_2" ]; then
    echo "  Viewing division: $DIV_SLUG_2..."
    VIEW_DIV2=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG/divisions/$DIV_SLUG_2" \
      -H "Accept: application/json" \
      -H "User-Agent: $USER_AGENT" \
      -H "Authorization: Bearer $TOKEN" \
      -w "\nHTTP_STATUS:%{http_code}" \
      -s)
    STATUS_DIV2=$(echo "$VIEW_DIV2" | grep "HTTP_STATUS" | cut -d':' -f2)
    echo "    Status: $STATUS_DIV2"
    sleep 1
  fi

  # View Provision 1
  if [ ! -z "$PROV_SLUG_1" ]; then
    echo "  Viewing provision: $PROV_SLUG_1..."
    VIEW_PROV1=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG/provisions/$PROV_SLUG_1" \
      -H "Accept: application/json" \
      -H "User-Agent: $USER_AGENT" \
      -H "Authorization: Bearer $TOKEN" \
      -w "\nHTTP_STATUS:%{http_code}" \
      -s)
    STATUS_PROV1=$(echo "$VIEW_PROV1" | grep "HTTP_STATUS" | cut -d':' -f2)
    echo "    Status: $STATUS_PROV1"
    sleep 1
  fi

  # View Provision 2
  if [ ! -z "$PROV_SLUG_2" ]; then
    echo "  Viewing provision: $PROV_SLUG_2..."
    VIEW_PROV2=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG/provisions/$PROV_SLUG_2" \
      -H "Accept: application/json" \
      -H "User-Agent: $USER_AGENT" \
      -H "Authorization: Bearer $TOKEN" \
      -w "\nHTTP_STATUS:%{http_code}" \
      -s)
    STATUS_PROV2=$(echo "$VIEW_PROV2" | grep "HTTP_STATUS" | cut -d':' -f2)
    echo "    Status: $STATUS_PROV2"
    sleep 1
  fi

  echo ""
  sleep 2
done

echo "✓ Completed 12 total views (2 divisions × 3 + 2 provisions × 3)"
echo ""

# Wait for views to be processed
echo "Waiting 3 seconds for views to be processed..."
sleep 3
echo ""

echo "Step 5: Testing trending divisions endpoint..."
echo "-----------------------------------------------"

TRENDING_DIV_RESPONSE=$(curl -X GET "$API_URL/trending/divisions?page=1&per_page=12&time_range=week" \
  -H "Accept: application/json" \
  -H "User-Agent: $USER_AGENT" \
  -w "\nHTTP_STATUS:%{http_code}" \
  -s)

HTTP_STATUS=$(echo "$TRENDING_DIV_RESPONSE" | grep "HTTP_STATUS" | cut -d':' -f2)
TRENDING_DIV_DATA=$(echo "$TRENDING_DIV_RESPONSE" | sed '/HTTP_STATUS/d')

echo "$TRENDING_DIV_DATA" | python -m json.tool 2>/dev/null || echo "$TRENDING_DIV_DATA"
echo ""
echo "HTTP Status: $HTTP_STATUS"
echo ""

if [ "$HTTP_STATUS" = "200" ]; then
  echo "✓ Trending divisions endpoint is working!"

  TOTAL_DIV=$(echo "$TRENDING_DIV_DATA" | grep -o '"total":[0-9]*' | head -1 | cut -d':' -f2)
  echo "  Total trending divisions: $TOTAL_DIV"
  echo ""

  # Check if our viewed divisions appear
  echo "  Validation: Checking if viewed divisions are trending..."
  if [ ! -z "$DIV_ID_1" ]; then
    FOUND_DIV1=$(echo "$TRENDING_DIV_DATA" | grep -c "\"id\":$DIV_ID_1" || echo "0")
    if [ "$FOUND_DIV1" -gt "0" ]; then
      echo "  ✓ Division 1 (ID: $DIV_ID_1) found in trending results"
    else
      echo "  ⚠️  Division 1 (ID: $DIV_ID_1) not found in trending results"
    fi
  fi

  if [ ! -z "$DIV_ID_2" ]; then
    FOUND_DIV2=$(echo "$TRENDING_DIV_DATA" | grep -c "\"id\":$DIV_ID_2" || echo "0")
    if [ "$FOUND_DIV2" -gt "0" ]; then
      echo "  ✓ Division 2 (ID: $DIV_ID_2) found in trending results"
    else
      echo "  ⚠️  Division 2 (ID: $DIV_ID_2) not found in trending results"
    fi
  fi
else
  echo "❌ Trending divisions endpoint failed!"
fi

echo ""
echo "Step 6: Testing trending provisions endpoint..."
echo "------------------------------------------------"

TRENDING_PROV_RESPONSE=$(curl -X GET "$API_URL/trending/provisions?page=1&per_page=12&time_range=week" \
  -H "Accept: application/json" \
  -H "User-Agent: $USER_AGENT" \
  -w "\nHTTP_STATUS:%{http_code}" \
  -s)

HTTP_STATUS_PROV=$(echo "$TRENDING_PROV_RESPONSE" | grep "HTTP_STATUS" | cut -d':' -f2)
TRENDING_PROV_DATA=$(echo "$TRENDING_PROV_RESPONSE" | sed '/HTTP_STATUS/d')

echo "$TRENDING_PROV_DATA" | python -m json.tool 2>/dev/null || echo "$TRENDING_PROV_DATA"
echo ""
echo "HTTP Status: $HTTP_STATUS_PROV"
echo ""

if [ "$HTTP_STATUS_PROV" = "200" ]; then
  echo "✓ Trending provisions endpoint is working!"

  TOTAL_PROV=$(echo "$TRENDING_PROV_DATA" | grep -o '"total":[0-9]*' | head -1 | cut -d':' -f2)
  echo "  Total trending provisions: $TOTAL_PROV"
  echo ""

  # Check if our viewed provisions appear
  echo "  Validation: Checking if viewed provisions are trending..."
  if [ ! -z "$PROV_ID_1" ]; then
    FOUND_PROV1=$(echo "$TRENDING_PROV_DATA" | grep -c "\"id\":$PROV_ID_1" || echo "0")
    if [ "$FOUND_PROV1" -gt "0" ]; then
      echo "  ✓ Provision 1 (ID: $PROV_ID_1) found in trending results"
    else
      echo "  ⚠️  Provision 1 (ID: $PROV_ID_1) not found in trending results"
    fi
  fi

  if [ ! -z "$PROV_ID_2" ]; then
    FOUND_PROV2=$(echo "$TRENDING_PROV_DATA" | grep -c "\"id\":$PROV_ID_2" || echo "0")
    if [ "$FOUND_PROV2" -gt "0" ]; then
      echo "  ✓ Provision 2 (ID: $PROV_ID_2) found in trending results"
    else
      echo "  ⚠️  Provision 2 (ID: $PROV_ID_2) not found in trending results"
    fi
  fi
else
  echo "❌ Trending provisions endpoint failed!"
fi

echo ""
echo "========================================================="
echo "Test completed!"
echo "========================================================="
echo ""
echo "Summary:"
echo "  Test user: $TEST_EMAIL"
echo "  Statute: $STATUTE_SLUG"
echo ""
echo "  Divisions viewed (3 times each):"
echo "    - $DIV_SLUG_1 (ID: ${DIV_ID_1:-unknown})"
echo "    - $DIV_SLUG_2 (ID: ${DIV_ID_2:-unknown})"
echo ""
echo "  Provisions viewed (3 times each):"
echo "    - $PROV_SLUG_1 (ID: ${PROV_ID_1:-unknown})"
echo "    - $PROV_SLUG_2 (ID: ${PROV_ID_2:-unknown})"
echo ""
echo "  Results:"
echo "    Trending divisions status: $HTTP_STATUS (Total: ${TOTAL_DIV:-0})"
echo "    Trending provisions status: $HTTP_STATUS_PROV (Total: ${TOTAL_PROV:-0})"
echo ""
