#!/bin/bash

# Test Trending API Endpoints
API_URL="http://localhost:8000/api"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Testing Trending API Endpoints ===${NC}"
echo

# Function to make API call with guest token
make_api_call() {
    local endpoint="$1"
    local description="$2"
    
    echo -e "${YELLOW}Testing: $description${NC}"
    echo "Endpoint: $API_URL$endpoint"
    
    # Use guest token from previous tests
    local guest_token="10|luHE8vYEjGvA0P12yGlQLseEhqMkEDDAqHEJABfxf353e17f"
    
    response=$(curl -s -H "Authorization: Bearer $guest_token" \
                  -H "Content-Type: application/json" \
                  "$API_URL$endpoint")
    
    # Check if response is valid JSON
    if echo "$response" | jq . > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Valid JSON response${NC}"
        status=$(echo "$response" | jq -r '.status // "unknown"')
        if [ "$status" = "success" ]; then
            echo -e "${GREEN}✓ API returned success${NC}"
            # Show data structure
            echo "$response" | jq -r '.data | keys[]' 2>/dev/null | head -5 | sed 's/^/  - /'
        else
            echo -e "${RED}✗ API error: $(echo "$response" | jq -r '.message // "Unknown error"')${NC}"
            echo "$response" | jq '.errors // empty' 2>/dev/null
        fi
    else
        echo -e "${RED}✗ Invalid JSON response${NC}"
        echo "$response" | head -200
    fi
    echo
}

# Test basic trending endpoint
make_api_call "/trending" "General trending (all content types)"

# Test trending stats
make_api_call "/trending/stats" "Trending statistics"

# Test content-specific trending
make_api_call "/trending/cases" "Trending cases"
make_api_call "/trending/statutes" "Trending statutes"
make_api_call "/trending/notes" "Trending notes"
make_api_call "/trending/folders" "Trending folders"

# Test with filters
make_api_call "/trending?time_range=today" "Today's trending content"
make_api_call "/trending?time_range=month&per_page=5" "Monthly trending (5 items)"
make_api_call "/trending/cases?time_range=week&country=Nigeria" "Weekly trending cases in Nigeria"

echo -e "${BLUE}=== Trending API Testing Complete ===${NC}"