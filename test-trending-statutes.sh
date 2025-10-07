#!/bin/bash

# Test script for trending statutes API endpoint
API_URL="https://rest.lawexa.com/api"
USER_AGENT="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"

echo "================================================="
echo "Testing Trending Statutes Endpoint"
echo "================================================="
echo ""

# Generate random email to avoid conflicts
TIMESTAMP=$(date +%s)
RANDOM_NUM=$RANDOM
TEST_EMAIL="test_statute_trending_${TIMESTAMP}_${RANDOM_NUM}@example.com"
TEST_PASSWORD="TestPassword123!"
TEST_NAME="Statute Trending Tester"

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

# First, let's get a list of statutes to view
echo "Step 3: Fetching available statutes..."
echo "---------------------------------------"

STATUTES_LIST=$(curl -X GET "$API_URL/statutes?per_page=5" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "User-Agent: $USER_AGENT" \
  -H "Authorization: Bearer $TOKEN" \
  -s)

# Extract 3 statute slugs (trying different patterns to find them)
STATUTE_SLUG_1=$(echo "$STATUTES_LIST" | grep -o '"slug":"[^"]*"' | head -1 | cut -d':' -f2 | tr -d '"')
STATUTE_SLUG_2=$(echo "$STATUTES_LIST" | grep -o '"slug":"[^"]*"' | head -2 | tail -1 | cut -d':' -f2 | tr -d '"')
STATUTE_SLUG_3=$(echo "$STATUTES_LIST" | grep -o '"slug":"[^"]*"' | head -3 | tail -1 | cut -d':' -f2 | tr -d '"')

if [ -z "$STATUTE_SLUG_1" ] || [ -z "$STATUTE_SLUG_2" ] || [ -z "$STATUTE_SLUG_3" ]; then
  echo "⚠️  Could not extract statute slugs from list, using default slugs"
  STATUTE_SLUG_1="evidence-act-1975"
  STATUTE_SLUG_2="criminal-offences-act-1960"
  STATUTE_SLUG_3="courts-act-1993"
fi

# Also extract IDs for validation later
STATUTE_ID_1=$(echo "$STATUTES_LIST" | grep -B2 "\"slug\":\"$STATUTE_SLUG_1\"" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)
STATUTE_ID_2=$(echo "$STATUTES_LIST" | grep -B2 "\"slug\":\"$STATUTE_SLUG_2\"" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)
STATUTE_ID_3=$(echo "$STATUTES_LIST" | grep -B2 "\"slug\":\"$STATUTE_SLUG_3\"" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)

echo "✓ Selected statutes:"
echo "  1. $STATUTE_SLUG_1 (ID: ${STATUTE_ID_1:-unknown})"
echo "  2. $STATUTE_SLUG_2 (ID: ${STATUTE_ID_2:-unknown})"
echo "  3. $STATUTE_SLUG_3 (ID: ${STATUTE_ID_3:-unknown})"
echo ""

echo "Step 4: Viewing each statute twice to generate trending data..."
echo "----------------------------------------------------------------"

for i in 1 2; do
  echo "Round $i of views:"

  # View Statute 1
  echo "  Viewing statute $STATUTE_SLUG_1..."
  VIEW_1=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG_1" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "User-Agent: $USER_AGENT" \
    -H "Authorization: Bearer $TOKEN" \
    -w "\nHTTP_STATUS:%{http_code}" \
    -s)
  STATUS_1=$(echo "$VIEW_1" | grep "HTTP_STATUS" | cut -d':' -f2)
  echo "  Status: $STATUS_1"

  sleep 1

  # View Statute 2
  echo "  Viewing statute $STATUTE_SLUG_2..."
  VIEW_2=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG_2" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "User-Agent: $USER_AGENT" \
    -H "Authorization: Bearer $TOKEN" \
    -w "\nHTTP_STATUS:%{http_code}" \
    -s)
  STATUS_2=$(echo "$VIEW_2" | grep "HTTP_STATUS" | cut -d':' -f2)
  echo "  Status: $STATUS_2"

  sleep 1

  # View Statute 3
  echo "  Viewing statute $STATUTE_SLUG_3..."
  VIEW_3=$(curl -X GET "$API_URL/statutes/$STATUTE_SLUG_3" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "User-Agent: $USER_AGENT" \
    -H "Authorization: Bearer $TOKEN" \
    -w "\nHTTP_STATUS:%{http_code}" \
    -s)
  STATUS_3=$(echo "$VIEW_3" | grep "HTTP_STATUS" | cut -d':' -f2)
  echo "  Status: $STATUS_3"

  echo ""
  sleep 2
done

echo "✓ Completed 6 statute views (3 statutes × 2 views each)"
echo ""

# Wait a moment for the views to be processed
echo "Waiting 3 seconds for views to be processed..."
sleep 3
echo ""

echo "Step 5: Testing trending statutes endpoint..."
echo "---------------------------------------------"

TRENDING_RESPONSE=$(curl -X GET "$API_URL/trending/statutes?page=1&per_page=12&time_range=week&country=yes" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "User-Agent: $USER_AGENT" \
  -w "\nHTTP_STATUS:%{http_code}" \
  -s)

HTTP_STATUS=$(echo "$TRENDING_RESPONSE" | grep "HTTP_STATUS" | cut -d':' -f2)
TRENDING_DATA=$(echo "$TRENDING_RESPONSE" | sed '/HTTP_STATUS/d')

echo "$TRENDING_DATA" | python -m json.tool 2>/dev/null || echo "$TRENDING_DATA"
echo ""
echo "HTTP Status: $HTTP_STATUS"
echo ""

if [ "$HTTP_STATUS" = "200" ]; then
  echo "✓ Trending statutes endpoint is working!"

  # Check if our viewed statutes appear in the results
  echo ""
  echo "Step 6: Validating results..."
  echo "-----------------------------"

  FOUND_1=$(echo "$TRENDING_DATA" | grep -c "\"id\":$STATUTE_ID_1" || echo "0")
  FOUND_2=$(echo "$TRENDING_DATA" | grep -c "\"id\":$STATUTE_ID_2" || echo "0")
  FOUND_3=$(echo "$TRENDING_DATA" | grep -c "\"id\":$STATUTE_ID_3" || echo "0")

  if [ "$FOUND_1" -gt "0" ]; then
    echo "✓ Statute $STATUTE_ID_1 found in trending results"
  else
    echo "⚠️  Statute $STATUTE_ID_1 not found in trending results"
  fi

  if [ "$FOUND_2" -gt "0" ]; then
    echo "✓ Statute $STATUTE_ID_2 found in trending results"
  else
    echo "⚠️  Statute $STATUTE_ID_2 not found in trending results"
  fi

  if [ "$FOUND_3" -gt "0" ]; then
    echo "✓ Statute $STATUTE_ID_3 found in trending results"
  else
    echo "⚠️  Statute $STATUTE_ID_3 not found in trending results"
  fi

  # Extract total count
  TOTAL=$(echo "$TRENDING_DATA" | grep -o '"total":[0-9]*' | head -1 | cut -d':' -f2)
  echo ""
  echo "Total trending statutes: $TOTAL"

else
  echo "❌ Trending statutes endpoint failed!"
fi

echo ""
echo "================================================="
echo "Test completed!"
echo "================================================="
echo ""
echo "Summary:"
echo "  Test user: $TEST_EMAIL"
echo "  Statutes viewed:"
echo "    - $STATUTE_SLUG_1 (ID: ${STATUTE_ID_1:-unknown})"
echo "    - $STATUTE_SLUG_2 (ID: ${STATUTE_ID_2:-unknown})"
echo "    - $STATUTE_SLUG_3 (ID: ${STATUTE_ID_3:-unknown})"
echo "  Views per statute: 2 times each"
echo "  Endpoint tested: /api/trending/statutes"
echo "  Final status: $HTTP_STATUS"
echo ""
