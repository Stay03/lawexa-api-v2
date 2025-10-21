#!/bin/bash

# Set environment variables
export API_URL='http://127.0.0.1:8000/api'
export USER_TOKEN='474|u8NxHnemqAXtoY2B4HhDVWEnAu2lhuHxHRWUgkUG8619c21d'
export ADMIN_TOKEN='475|Ej5XkATwXLKJyxEfqRIuDMg9DAp0mLquOsUVGfoZ38e116c1'

echo "Running Content Request System Tests..."
echo "API_URL: $API_URL"
echo "USER_TOKEN: ${USER_TOKEN:0:20}..."
echo "ADMIN_TOKEN: ${ADMIN_TOKEN:0:20}..."
echo ""

# Change to Laravel project root and run the test script
cd ../../../../../
bash Docs/v2/implementation/content-request-system/tests/test-content-request-system.sh
