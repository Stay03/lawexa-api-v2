#!/bin/bash

API_URL="http://localhost:8000/api"
TOKEN="454|jCctYvS5tcXqwcvJGxtjaOvRjx2EdaO8uEHj7goq41f82cc5"

echo "=== Testing Bookmark Statute Division (ID: 27) ==="
curl -X POST "$API_URL/bookmarks" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"bookmarkable_type":"App\\Models\\StatuteDivision","bookmarkable_id":27}'

echo -e "\n\n=== Testing Bookmark Statute Provision (ID: 79) ==="
curl -X POST "$API_URL/bookmarks" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"bookmarkable_type":"App\\Models\\StatuteProvision","bookmarkable_id":79}'

echo -e "\n\n=== Checking Bookmarks List ==="
curl -X GET "$API_URL/bookmarks" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

echo -e "\n\n=== Checking Bookmark Stats ==="
curl -X GET "$API_URL/bookmarks/stats" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
