#!/bin/bash

# Test script for trending API endpoints
API_URL="http://127.0.0.1:8000/api"

echo "Testing Trending API Endpoints..."
echo "================================="

echo ""
echo "1. Testing basic trending endpoint..."
curl -X GET "$API_URL/trending" \
  -H "Content-Type: application/json" \
  -w "\nHTTP Status: %{http_code}\n" \
  --max-time 10 \
  -s

echo ""
echo "2. Testing trending cases endpoint..."
curl -X GET "$API_URL/trending/cases" \
  -H "Content-Type: application/json" \
  -w "\nHTTP Status: %{http_code}\n" \
  --max-time 10 \
  -s

echo ""
echo "3. Testing trending cases with Nigeria filter..."
curl -X GET "$API_URL/trending/cases?country=Nigeria" \
  -H "Content-Type: application/json" \
  -w "\nHTTP Status: %{http_code}\n" \
  --max-time 10 \
  -s

echo ""
echo "Test completed."
