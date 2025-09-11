#!/bin/bash

API_URL="http://localhost:8000/api"
ADMIN_TOKEN='146|rBSikLFG5SWU57944C9TzVXZNgbg5UjknP0AWBZQf13baf38'

echo "Testing view cooldown behavior..."
echo "Current cooldown settings:"
grep -i "view_cooldown" .env

echo -e "\n=== Testing rapid views on same case (should be blocked by cooldown) ==="

# Get first case ID
echo "Getting case list..."
CASE_RESPONSE=$(curl -s -H "Authorization: Bearer $ADMIN_TOKEN" "$API_URL/cases?limit=1")
CASE_ID=$(echo $CASE_RESPONSE | grep -o '"id":[0-9]*' | head -1 | sed 's/"id"://')

if [ -z "$CASE_ID" ]; then
    echo "Failed to get case ID"
    exit 1
fi

echo "Testing with Case ID: $CASE_ID"

echo -e "\n--- View 1 (should succeed) ---"
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" "$API_URL/cases/$CASE_ID" > /dev/null
echo "First view completed at $(date)"

echo -e "\n--- View 2 immediately after (should be blocked by cooldown) ---"
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" "$API_URL/cases/$CASE_ID" > /dev/null
echo "Second view completed at $(date)"

echo -e "\n--- View 3 after 1 second (should still be blocked) ---"
sleep 1
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" "$API_URL/cases/$CASE_ID" > /dev/null
echo "Third view completed at $(date)"

echo -e "\n--- View 4 after 3 seconds total (should succeed after cooldown) ---"
sleep 2
curl -s -H "Authorization: Bearer $ADMIN_TOKEN" "$API_URL/cases/$CASE_ID" > /dev/null
echo "Fourth view completed at $(date)"

echo -e "\n=== Checking view count in database ==="
# Count views for this case
echo "SELECT COUNT(*) as view_count FROM model_views WHERE viewable_type = 'App\\\\Models\\\\LawCase' AND viewable_id = $CASE_ID;" | php artisan tinker | tail -1