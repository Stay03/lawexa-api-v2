#!/bin/bash

# Test script for My Notes API endpoint
API_URL="${API_URL:-http://127.0.0.1:8000/api}"

# You need to replace this with a valid user token
# Get one by logging in: POST /api/login
# Or use an environment variable: export USER_TOKEN="your_token"
USER_TOKEN="${USER_TOKEN:-your_user_token_here}"

echo "Testing My Notes API Endpoint..."
echo "================================="
echo ""

echo "Note: Make sure to replace USER_TOKEN in the script with a valid token"
echo ""

# Test 1: Get my notes without authentication (should fail)
echo "1. Testing my-notes without authentication (should fail with 401)..."
curl -X GET "$API_URL/notes/my-notes" \
  -H "Content-Type: application/json" \
  -w "\nHTTP Status: %{http_code}\n" \
  -s
echo ""

# Test 2: Get my notes with authentication
echo "2. Testing my-notes with authentication..."
curl -X GET "$API_URL/notes/my-notes" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -w "\nHTTP Status: %{http_code}\n" \
  -s
echo ""

# Test 3: Get my notes with search filter
echo "3. Testing my-notes with search filter..."
curl -X GET "$API_URL/notes/my-notes?search=test" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -w "\nHTTP Status: %{http_code}\n" \
  -s
echo ""

# Test 4: Get my notes with tag filter
echo "4. Testing my-notes with tag filter..."
curl -X GET "$API_URL/notes/my-notes?tag=work" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -w "\nHTTP Status: %{http_code}\n" \
  -s
echo ""

# Test 5: Get my private notes only
echo "5. Testing my-notes with is_private=true filter..."
curl -X GET "$API_URL/notes/my-notes?is_private=true" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -w "\nHTTP Status: %{http_code}\n" \
  -s
echo ""

# Test 6: Get my public notes only
echo "6. Testing my-notes with is_private=false filter..."
curl -X GET "$API_URL/notes/my-notes?is_private=false" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -w "\nHTTP Status: %{http_code}\n" \
  -s
echo ""

# Test 7: Get my notes with pagination
echo "7. Testing my-notes with pagination (per_page=5, page=1)..."
curl -X GET "$API_URL/notes/my-notes?per_page=5&page=1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -w "\nHTTP Status: %{http_code}\n" \
  -s
echo ""

# Test 8: Get my notes with combined filters
echo "8. Testing my-notes with combined filters (search + tag + privacy)..."
curl -X GET "$API_URL/notes/my-notes?search=meeting&tag=work&is_private=false" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -w "\nHTTP Status: %{http_code}\n" \
  -s
echo ""

echo "Test completed."
echo ""
echo "Note: To run this test properly, you need to:"
echo "1. Have the Laravel server running (php artisan serve)"
echo "2. Replace USER_TOKEN with a valid authentication token"
echo "3. Have some notes in your account to see results"
