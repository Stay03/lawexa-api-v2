#!/bin/bash

# Test View Statistics Endpoints
echo "Testing View Statistics Endpoints..."

# Set API base URL
API_URL="${API_URL:-http://localhost:8000/api}"

# Admin token (will need to be set as environment variable)
ADMIN_TOKEN="${ADMIN_TOKEN:-}"

# Guest token for user endpoints
GUEST_TOKEN="${GUEST_TOKEN:-}"

echo "Using API URL: $API_URL"

if [ -z "$ADMIN_TOKEN" ]; then
    echo "Warning: ADMIN_TOKEN not set. Admin endpoints will fail."
fi

if [ -z "$GUEST_TOKEN" ]; then
    echo "Warning: GUEST_TOKEN not set. User endpoints will fail."
fi

echo ""
echo "=== Testing User Endpoints (Public/User Auth) ==="

echo ""
echo "1. Testing Popular Content (GET /views/stats/popular)"
curl -s -H "Accept: application/json" \
     "$API_URL/views/stats/popular?period=week&limit=5" \
     | jq '.' 2>/dev/null || echo "Failed to parse JSON"

if [ ! -z "$GUEST_TOKEN" ]; then
    echo ""
    echo "2. Testing My Activity (GET /views/stats/my-activity)"
    curl -s -H "Accept: application/json" \
         -H "Authorization: Bearer $GUEST_TOKEN" \
         "$API_URL/views/stats/my-activity?limit=10" \
         | jq '.' 2>/dev/null || echo "Failed to parse JSON"
fi

if [ ! -z "$ADMIN_TOKEN" ]; then
    echo ""
    echo "=== Testing Admin Endpoints (Admin Auth Required) ==="

    echo ""
    echo "3. Testing Overview Stats (GET /admin/views/stats/overview)"
    curl -s -H "Accept: application/json" \
         -H "Authorization: Bearer $ADMIN_TOKEN" \
         "$API_URL/admin/views/stats/overview" \
         | jq '.' 2>/dev/null || echo "Failed to parse JSON"

    echo ""
    echo "4. Testing Model Stats (GET /admin/views/stats/models)"
    curl -s -H "Accept: application/json" \
         -H "Authorization: Bearer $ADMIN_TOKEN" \
         "$API_URL/admin/views/stats/models" \
         | jq '.' 2>/dev/null || echo "Failed to parse JSON"

    echo ""
    echo "5. Testing User Stats (GET /admin/views/stats/users)"
    curl -s -H "Accept: application/json" \
         -H "Authorization: Bearer $ADMIN_TOKEN" \
         "$API_URL/admin/views/stats/users?limit=5" \
         | jq '.' 2>/dev/null || echo "Failed to parse JSON"

    echo ""
    echo "6. Testing Geography Stats (GET /admin/views/stats/geography)"
    curl -s -H "Accept: application/json" \
         -H "Authorization: Bearer $ADMIN_TOKEN" \
         "$API_URL/admin/views/stats/geography?group_by=country" \
         | jq '.' 2>/dev/null || echo "Failed to parse JSON"

    echo ""
    echo "7. Testing Device Stats (GET /admin/views/stats/devices)"
    curl -s -H "Accept: application/json" \
         -H "Authorization: Bearer $ADMIN_TOKEN" \
         "$API_URL/admin/views/stats/devices" \
         | jq '.' 2>/dev/null || echo "Failed to parse JSON"

    echo ""
    echo "8. Testing Trends Stats (GET /admin/views/stats/trends)"
    curl -s -H "Accept: application/json" \
         -H "Authorization: Bearer $ADMIN_TOKEN" \
         "$API_URL/admin/views/stats/trends?interval=day" \
         | jq '.' 2>/dev/null || echo "Failed to parse JSON"
fi

echo ""
echo "=== Testing Complete ==="
echo "To run this script with tokens:"
echo "export API_URL='http://localhost:8000/api'"
echo "export ADMIN_TOKEN='your-admin-token'"
echo "export GUEST_TOKEN='your-guest-token'"
echo "./test-view-stats-endpoints.sh"