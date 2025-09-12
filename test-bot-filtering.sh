#!/bin/bash

# Test script for bot filtering functionality in view stats API
# Run with: bash test-bot-filtering.sh

API_URL="${API_URL:-http://localhost:8000/api}"
ADMIN_TOKEN="${ADMIN_TOKEN:-}"

if [ -z "$ADMIN_TOKEN" ]; then
    echo "Error: ADMIN_TOKEN environment variable is required"
    echo "Set it like: export ADMIN_TOKEN='your_admin_token'"
    exit 1
fi

echo "=== Testing Bot Filtering in View Stats API ==="
echo "API URL: $API_URL"
echo

# Test 1: Basic views endpoint with bot filtering
echo "Test 1: /admin/views with is_bot=true filter"
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" \
     -H "Accept: application/json" \
     "$API_URL/admin/views?is_bot=true&per_page=5" | jq '.'
echo
echo "---"

# Test 2: Views endpoint with human filtering
echo "Test 2: /admin/views with is_bot=false filter (human views)"
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" \
     -H "Accept: application/json" \
     "$API_URL/admin/views?is_bot=false&per_page=5" | jq '.'
echo
echo "---"

# Test 3: Filter by search engine bots
echo "Test 3: /admin/views with is_search_engine=true filter"
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" \
     -H "Accept: application/json" \
     "$API_URL/admin/views?is_search_engine=true&per_page=5" | jq '.'
echo
echo "---"

# Test 4: Filter by social media bots
echo "Test 4: /admin/views with is_social_media=true filter"
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" \
     -H "Accept: application/json" \
     "$API_URL/admin/views?is_social_media=true&per_page=5" | jq '.'
echo
echo "---"

# Test 5: Sort by bot status
echo "Test 5: /admin/views with sort_by=bot_status"
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" \
     -H "Accept: application/json" \
     "$API_URL/admin/views?sort_by=bot_status&per_page=5" | jq '.'
echo
echo "---"

# Test 6: Dashboard with bot filtering
echo "Test 6: /admin/views/stats/dashboard with is_bot=true filter"
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" \
     -H "Accept: application/json" \
     "$API_URL/admin/views/stats/dashboard?is_bot=true" | jq '.'
echo
echo "---"

# Test 7: Dashboard with human filtering
echo "Test 7: /admin/views/stats/dashboard with is_bot=false filter"
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" \
     -H "Accept: application/json" \
     "$API_URL/admin/views/stats/dashboard?is_bot=false" | jq '.'
echo
echo "---"

# Test 8: Dashboard without filtering (should show bot breakdown)
echo "Test 8: /admin/views/stats/dashboard without filtering (check bot_breakdown)"
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" \
     -H "Accept: application/json" \
     "$API_URL/admin/views/stats/dashboard" | jq '.data.analytics.bot_breakdown'
echo

echo "=== Bot Filtering Tests Completed ==="