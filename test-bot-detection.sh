#!/bin/bash

# Test script for bot detection functionality
# This script tests various bot user agents against the API endpoints

BASE_URL="http://localhost:8000/api"

echo "🤖 Testing Bot Detection Implementation"
echo "======================================"

# Test different bot user agents
declare -a BOT_AGENTS=(
    "Googlebot/2.1 (+http://www.google.com/bot.html)"
    "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)"
    "facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)"
    "Twitterbot/1.0"
    "LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com/)"
    "WhatsApp/2.19.81"
)

# Test endpoints to check
declare -a ENDPOINTS=(
    "cases/first-case-example"
    "statutes/first-statute-example" 
    "notes/1"
)

echo ""
echo "Testing Bot User Agents:"
echo "----------------------"

for bot_agent in "${BOT_AGENTS[@]}"; do
    echo ""
    echo "Testing with: $bot_agent"
    echo "----------------------------------------"
    
    for endpoint in "${ENDPOINTS[@]}"; do
        echo "  Testing endpoint: $endpoint"
        
        response=$(curl -s -H "User-Agent: $bot_agent" "$BASE_URL/$endpoint" 2>/dev/null)
        
        # Check if response contains bot indicator
        if echo "$response" | grep -q '"isBot":true'; then
            echo "    ✅ Bot detected successfully"
            
            # Extract bot info if present
            bot_name=$(echo "$response" | grep -o '"bot_name":"[^"]*"' | cut -d'"' -f4)
            if [ ! -z "$bot_name" ]; then
                echo "    📊 Bot identified as: $bot_name"
            fi
            
            # For case endpoints, check if sensitive fields are filtered
            if [[ "$endpoint" == *"cases"* ]]; then
                if echo "$response" | grep -q '"report"'; then
                    echo "    ⚠️  WARNING: Report field present in bot response"
                else
                    echo "    ✅ Sensitive content filtered (report field removed)"
                fi
            fi
            
        else
            echo "    ❌ Bot detection failed"
        fi
    done
done

echo ""
echo "Testing Human User Agent:"
echo "------------------------"

human_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36"

for endpoint in "${ENDPOINTS[@]}"; do
    echo "  Testing endpoint: $endpoint"
    
    response=$(curl -s -H "User-Agent: $human_agent" "$BASE_URL/$endpoint" 2>/dev/null)
    
    # Check if response does NOT contain bot indicator
    if echo "$response" | grep -q '"isBot":true'; then
        echo "    ❌ Human incorrectly detected as bot"
    else
        echo "    ✅ Human user agent handled correctly"
        
        # For case endpoints, check if full content is available
        if [[ "$endpoint" == *"cases"* ]]; then
            if echo "$response" | grep -q '"report"'; then
                echo "    ✅ Full content available (report field present)"
            else
                echo "    ⚠️  WARNING: Report field missing in human response"
            fi
        fi
    fi
done

echo ""
echo "Testing Complete! 🎉"
echo "==================="
echo ""
echo "📝 Notes:"
echo "- Bot requests should show 'isBot': true"
echo "- Bot case responses should exclude 'report' and 'case_report_text'"
echo "- Human requests should show full content"
echo "- Check server logs for bot detection logs if enabled"