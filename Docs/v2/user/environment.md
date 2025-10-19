# API Testing Documentation

## Overview
This documentation provides environment setup details and testing commands for API testing with the dedicated test account.

## Test Account Credentials

### Account Details
- **Email**: `testcasefilter@lawexa.com`
- **Password**: `password123`
- **User ID**: `347`
- **Account Status**: Verified
- **Authentication Token**: `466|XjMKhH0emA79cym1vBdDmtQAA4fjxZSA9JrC8Rtz58a64e90`

### Important Notes
- The account is verified and ready for authenticated API access
- Email verification was completed via Tinker
- Token can be used for testing authenticated endpoints

## Environment Setup

### Required Environment Variables
```bash
# API Base URL (configure based on your environment)
export API_URL="http://your-api-domain.com/api"

# Authentication Token
export TEST_USER_TOKEN="466|XjMKhH0emA79cym1vBdDmtQAA4fjxZSA9JrC8Rtz58a64e90"

# Real User Agent for testing
export USER_AGENT="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
```

### Local Development Example
```bash
export API_URL="http://localhost:8000/api"
```

### Production Example
```bash
export API_URL="https://your-production-domain.com/api"
```

## Authentication

### Login Command Template
Use this command to obtain a fresh authentication token:

```bash
curl -X POST "${API_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -H "User-Agent: ${USER_AGENT}" \
  -d '{
    "email": "testcasefilter@lawexa.com",
    "password": "password123"
  }'
```

### Authentication Headers
All authenticated requests must include these headers:

```bash
-H "Content-Type: application/json" \
-H "Authorization: Bearer ${TEST_USER_TOKEN}" \
-H "User-Agent: ${USER_AGENT}"
```

## Testing Command Templates

### Basic GET Request Template
```bash
curl -X GET "${API_URL}/{endpoint}" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TEST_USER_TOKEN}" \
  -H "User-Agent: ${USER_AGENT}"
```

### GET Request with Query Parameters
```bash
curl -X GET "${API_URL}/{endpoint}?{param1}={value1}&{param2}={value2}" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TEST_USER_TOKEN}" \
  -H "User-Agent: ${USER_AGENT}"
```

### POST Request Template
```bash
curl -X POST "${API_URL}/{endpoint}" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TEST_USER_TOKEN}" \
  -H "User-Agent: ${USER_AGENT}" \
  -d '{
    "field1": "value1",
    "field2": "value2"
  }'
```

### PUT Request Template
```bash
curl -X PUT "${API_URL}/{endpoint}/{id}" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TEST_USER_TOKEN}" \
  -H "User-Agent: ${USER_AGENT}" \
  -d '{
    "field1": "updated_value1",
    "field2": "updated_value2"
  }'
```

### DELETE Request Template
```bash
curl -X DELETE "${API_URL}/{endpoint}/{id}" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TEST_USER_TOKEN}" \
  -H "User-Agent: ${USER_AGENT}"
```

## Testing Best Practices

### 1. Environment Preparation
```bash
# Set environment variables
export API_URL="your-api-url"
export TEST_USER_TOKEN="your-token"
export USER_AGENT="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"

# Verify variables are set
echo "API URL: $API_URL"
echo "Token: $TEST_USER_TOKEN"
```

### 2. URL Encoding
Remember to URL encode special characters in query parameters:
- Spaces become `%20`
- Other special characters should be properly encoded

### 3. Response Format Checking
Use tools to format JSON responses for better readability:
```bash
# Using python (if available)
curl ... | python -m json.tool

# Using jq (if available)
curl ... | jq .
```

## Expected Response Formats

### Success Response Structure
```json
{
  "status": "success",
  "message": "Operation completed successfully",
  "data": {
    // Response data here
  }
}
```

### Error Response Structure
```json
{
  "status": "error",
  "message": "Error description",
  "data": null
}
```

### Validation Error Response
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": {
    "errors": {
      "field_name": ["Error message"]
    }
  }
}
```

## Authentication Testing

### Test Without Token (Should Fail)
```bash
curl -X GET "${API_URL}/protected-endpoint" \
  -H "Content-Type: application/json" \
  -H "User-Agent: ${USER_AGENT}"
```

### Test With Invalid Token (Should Fail)
```bash
curl -X GET "${API_URL}/protected-endpoint" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer invalid-token" \
  -H "User-Agent: ${USER_AGENT}"
```

### Test With Valid Token (Should Succeed)
```bash
curl -X GET "${API_URL}/protected-endpoint" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TEST_USER_TOKEN}" \
  -H "User-Agent: ${USER_AGENT}"
```

## Quick Start

1. **Set up environment variables** based on your target environment
2. **Test authentication** with the provided credentials
3. **Use the command templates** to test specific endpoints
4. **Replace placeholders** (`{endpoint}`, `{parameters}`, etc.) with actual values
5. **Verify responses** match expected formats

## Notes

- The test account has been verified and should work with any properly configured API endpoint
- All commands use a real browser user agent to avoid bot detection
- Commands are designed to be copied and pasted directly into terminal
- Adjust the `API_URL` variable based on your testing environment (localhost, staging, production)