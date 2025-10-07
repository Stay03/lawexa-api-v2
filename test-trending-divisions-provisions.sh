#!/bin/bash

# Test script for trending divisions and provisions API endpoints
API_URL="https://rest.lawexa.com/api"
USER_AGENT="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"

echo "========================================================="
echo "Testing Trending Divisions and Provisions Endpoints"
echo "========================================================="
echo ""

# Generate random email to avoid conflicts
TIMESTAMP=$(date +%s)
RANDOM_NUM=$RANDOM
TEST_EMAIL="test_div_prov_trending_${TIMESTAMP}_${RANDOM_NUM}@example.com"
TEST_PASSWORD="TestPassword123!"
TEST_NAME="Division Provision Tester"

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

# Extract token using grep and sed
TOKEN=$(echo "$LOGIN_DATA" | grep -o '"token":"[^"]*"' | head -1 | sed 's/"token":"\(.*\)"/\1/')

if [ -z "$TOKEN" ]; then
  echo "❌ Failed to extract token!"
  exit 1
fi

echo "✓ Logged in successfully"
echo "Token: ${TOKEN:0:20}..."
echo ""

# First, get a statute with divisions and provisions
echo "Step 3: Fetching a statute with divisions and provisions..."
echo "-----------------------------------------------------------"

STATUTE_SLUG="constitution-of-the-federal-republic-of-nigeria-1999"

# Get divisions list
echo "Fetching divisions..."
DIVISIONS_LIST=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG/divisions" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "User-Agent: $USER_AGENT" \
  -H "Authorization: Bearer $TOKEN" \
  -s)

# Extract 3 division slugs
DIV_SLUG_1=$(echo "$DIVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -1 | cut -d':' -f2 | tr -d '"')
DIV_SLUG_2=$(echo "$DIVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -2 | tail -1 | cut -d':' -f2 | tr -d '"')
DIV_SLUG_3=$(echo "$DIVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -3 | tail -1 | cut -d':' -f2 | tr -d '"')

if [ -z "$DIV_SLUG_1" ] || [ -z "$DIV_SLUG_2" ] || [ -z "$DIV_SLUG_3" ]; then
  echo "⚠️  Could not extract division slugs, trying alternative statute"
  STATUTE_SLUG="evidence-act-1975"

  DIVISIONS_LIST=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG/divisions" \
    -H "Accept: application/json" \
    -H "User-Agent: $USER_AGENT" \
    -H "Authorization: Bearer $TOKEN" \
    -s)

  DIV_SLUG_1=$(echo "$DIVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -1 | cut -d':' -f2 | tr -d '"')
  DIV_SLUG_2=$(echo "$DIVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -2 | tail -1 | cut -d':' -f2 | tr -d '"')
  DIV_SLUG_3=$(echo "$DIVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -3 | tail -1 | cut -d':' -f2 | tr -d '"')
fi

echo "✓ Selected divisions from $STATUTE_SLUG:"
echo "  1. $DIV_SLUG_1"
echo "  2. $DIV_SLUG_2"
echo "  3. $DIV_SLUG_3"
echo ""

# Get provisions list
echo "Fetching provisions..."
PROVISIONS_LIST=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG/provisions" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "User-Agent: $USER_AGENT" \
  -H "Authorization: Bearer $TOKEN" \
  -s)

# Extract 3 provision slugs
PROV_SLUG_1=$(echo "$PROVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -1 | cut -d':' -f2 | tr -d '"')
PROV_SLUG_2=$(echo "$PROVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -2 | tail -1 | cut -d':' -f2 | tr -d '"')
PROV_SLUG_3=$(echo "$PROVISIONS_LIST" | grep -o '"slug":"[^"]*"' | head -3 | tail -1 | cut -d':' -f2 | tr -d '"')

echo "✓ Selected provisions from $STATUTE_SLUG:"
echo "  1. $PROV_SLUG_1"
echo "  2. $PROV_SLUG_2"
echo "  3. $PROV_SLUG_3"
echo ""

echo "Step 4: Viewing divisions and provisions twice each..."
echo "-------------------------------------------------------"

for i in 1 2; do
  echo "Round $i of views:"
  echo ""

  # View divisions
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

  if [ ! -z "$DIV_SLUG_3" ]; then
    echo "  Viewing division: $DIV_SLUG_3..."
    VIEW_DIV3=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG/divisions/$DIV_SLUG_3" \
      -H "Accept: application/json" \
      -H "User-Agent: $USER_AGENT" \
      -H "Authorization: Bearer $TOKEN" \
      -w "\nHTTP_STATUS:%{http_code}" \
      -s)
    STATUS_DIV3=$(echo "$VIEW_DIV3" | grep "HTTP_STATUS" | cut -d':' -f2)
    echo "    Status: $STATUS_DIV3"
    sleep 1
  fi

  # View provisions
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

  if [ ! -z "$PROV_SLUG_3" ]; then
    echo "  Viewing provision: $PROV_SLUG_3..."
    VIEW_PROV3=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG/provisions/$PROV_SLUG_3" \
      -H "Accept: application/json" \
      -H "User-Agent: $USER_AGENT" \
      -H "Authorization: Bearer $TOKEN" \
      -w "\nHTTP_STATUS:%{http_code}" \
      -s)
    STATUS_PROV3=$(echo "$VIEW_PROV3" | grep "HTTP_STATUS" | cut -d':' -f2)
    echo "    Status: $STATUS_PROV3"
    sleep 1
  fi

  echo ""
  sleep 2
done

echo "✓ Completed all views"
echo ""

# Wait for views to be processed
echo "Waiting 3 seconds for views to be processed..."
sleep 3
echo ""

echo "Step 5: Testing trending divisions endpoint..."
echo "-----------------------------------------------"

TRENDING_DIV_RESPONSE=$(curl -X GET "$API_URL/trending/divisions?page=1&per_page=12&time_range=week&country=yes" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
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
else
  echo "❌ Trending divisions endpoint failed!"
fi

echo ""
echo "Step 6: Testing trending provisions endpoint..."
echo "------------------------------------------------"

TRENDING_PROV_RESPONSE=$(curl -X GET "$API_URL/trending/provisions?page=1&per_page=12&time_range=week&country=yes" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
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
echo "  Statute used: $STATUTE_SLUG"
echo "  Divisions viewed: $DIV_SLUG_1, $DIV_SLUG_2, $DIV_SLUG_3 (2 times each)"
echo "  Provisions viewed: $PROV_SLUG_1, $PROV_SLUG_2, $PROV_SLUG_3 (2 times each)"
echo "  Trending divisions status: $HTTP_STATUS"
echo "  Trending provisions status: $HTTP_STATUS_PROV"
echo ""
