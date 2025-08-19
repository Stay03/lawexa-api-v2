<?php

/**
 * Detailed Comments API Investigation Script
 * Investigates specific issues found in the initial test run
 */

class DetailedCommentsApiTester
{
    private $apiUrl;
    private $apiToken;

    public function __construct()
    {
        $this->loadCredentials();
    }

    private function loadCredentials()
    {
        $envContent = file_get_contents('.env.text');
        
        preg_match("/LIVE_API_TOKEN='([^']+)'/", $envContent, $tokenMatch);
        preg_match("/LIVE_API_URL='([^']+)'/", $envContent, $urlMatch);
        
        $this->apiToken = $tokenMatch[1] ?? null;
        $this->apiUrl = $urlMatch[1] ?? null;
        
        echo "✓ Using Live API: {$this->apiUrl}\n\n";
    }

    private function makeRequest($method, $endpoint, $data = null, $headers = [])
    {
        $url = $this->apiUrl . $endpoint;
        
        $defaultHeaders = [
            'Authorization: Bearer ' . $this->apiToken,
            'Accept: application/json',
            'User-Agent: Comments-Detailed-Tester/1.0'
        ];
        
        $allHeaders = array_merge($defaultHeaders, $headers);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_VERBOSE => false
        ]);

        if ($data !== null) {
            if (in_array('Content-Type: application/json', $allHeaders)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'status_code' => $httpCode,
            'body' => $response,
            'data' => json_decode($response, true),
            'curl_info' => $info,
            'curl_error' => $error
        ];
    }

    public function investigateIssues()
    {
        echo "🔍 DETAILED INVESTIGATION OF COMMENTS API ISSUES\n";
        echo "================================================\n\n";

        // Get a test issue ID first
        $issueId = $this->getTestIssueId();

        // Investigation 1: Invalid commentable_type handling
        echo "🔍 Investigation 1: Invalid commentable_type handling\n";
        echo "Expected: Should return 400 error for invalid types\n";
        echo "Finding: Returns 200 with empty results\n\n";
        
        $response1 = $this->makeRequest('GET', "/comments?commentable_type=InvalidType&commentable_id={$issueId}");
        echo "Request: GET /comments?commentable_type=InvalidType&commentable_id={$issueId}\n";
        echo "Status: {$response1['status_code']}\n";
        echo "Response: " . json_encode($response1['data'], JSON_PRETTY_PRINT) . "\n\n";

        // Investigation 2: Non-existent resource error code
        echo "🔍 Investigation 2: Non-existent resource error code\n";
        echo "Expected: Should return 404 status code\n";
        echo "Finding: Returns 400 status code but with correct error message\n\n";
        
        $response2 = $this->makeRequest('POST', '/comments', [
            'content' => 'Test comment',
            'commentable_type' => 'Issue',
            'commentable_id' => 999999
        ]);
        echo "Request: POST /comments (with non-existent issue ID 999999)\n";
        echo "Status: {$response2['status_code']}\n";
        echo "Response: " . json_encode($response2['data'], JSON_PRETTY_PRINT) . "\n\n";

        // Investigation 3: Authentication without token
        echo "🔍 Investigation 3: Authentication without token\n";
        echo "Expected: Should return 401 Unauthorized\n";
        echo "Finding: Returns 0 (connection issue or redirect)\n\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . "/comments?commentable_type=Issue&commentable_id={$issueId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false, // Don't follow redirects to see original response
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response3_body = curl_exec($ch);
        $response3_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response3_info = curl_getinfo($ch);
        curl_close($ch);
        
        echo "Request: GET /comments (without Authorization header)\n";
        echo "Status: {$response3_code}\n";
        echo "Response: {$response3_body}\n";
        echo "Curl Info: " . json_encode($response3_info, JSON_PRETTY_PRINT) . "\n\n";

        // Investigation 4: Pagination metadata
        echo "🔍 Investigation 4: Pagination metadata structure\n";
        echo "Expected: Should have meta and links in pagination response\n";
        echo "Finding: Need to check actual structure\n\n";
        
        $response4 = $this->makeRequest('GET', "/comments?commentable_type=Issue&commentable_id={$issueId}&per_page=5&page=1");
        echo "Request: GET /comments with pagination parameters\n";
        echo "Status: {$response4['status_code']}\n";
        echo "Response Structure:\n";
        $this->printStructure($response4['data']);
        echo "\n";

        // Investigation 5: Test successful comment creation and retrieval
        echo "🔍 Investigation 5: Complete comment workflow\n";
        echo "Testing: Create → Show → Update → Reply → Delete\n\n";
        
        // Create a comment
        $createResponse = $this->makeRequest('POST', '/comments', [
            'content' => 'Test comment for detailed investigation',
            'commentable_type' => 'Issue',
            'commentable_id' => $issueId
        ]);
        
        echo "Step 1 - Create Comment:\n";
        echo "Status: {$createResponse['status_code']}\n";
        echo "Response: " . json_encode($createResponse['data'], JSON_PRETTY_PRINT) . "\n\n";
        
        if ($createResponse['status_code'] === 201 && isset($createResponse['data']['data']['comment']['id'])) {
            $commentId = $createResponse['data']['data']['comment']['id'];
            
            // Show the comment
            $showResponse = $this->makeRequest('GET', "/comments/{$commentId}");
            echo "Step 2 - Show Comment:\n";
            echo "Status: {$showResponse['status_code']}\n";
            echo "Response: " . json_encode($showResponse['data'], JSON_PRETTY_PRINT) . "\n\n";
            
            // Update the comment
            $updateResponse = $this->makeRequest('PUT', "/comments/{$commentId}", [
                'content' => 'Updated comment content for detailed investigation'
            ]);
            echo "Step 3 - Update Comment:\n";
            echo "Status: {$updateResponse['status_code']}\n";
            echo "Response: " . json_encode($updateResponse['data'], JSON_PRETTY_PRINT) . "\n\n";
            
            // Create a reply
            $replyResponse = $this->makeRequest('POST', "/comments/{$commentId}/reply", [
                'content' => 'Test reply for detailed investigation'
            ], ['Content-Type: application/json']);
            echo "Step 4 - Create Reply:\n";
            echo "Status: {$replyResponse['status_code']}\n";
            echo "Response: " . json_encode($replyResponse['data'], JSON_PRETTY_PRINT) . "\n\n";
            
            if ($replyResponse['status_code'] === 201 && isset($replyResponse['data']['data']['comment']['id'])) {
                $replyId = $replyResponse['data']['data']['comment']['id'];
                
                // Delete the reply
                $deleteResponse = $this->makeRequest('DELETE', "/comments/{$replyId}");
                echo "Step 5 - Delete Reply:\n";
                echo "Status: {$deleteResponse['status_code']}\n";
                echo "Response: " . json_encode($deleteResponse['data'], JSON_PRETTY_PRINT) . "\n\n";
            }
            
            // Show updated comment with threading
            $finalShowResponse = $this->makeRequest('GET', "/comments/{$commentId}");
            echo "Step 6 - Final Comment State:\n";
            echo "Status: {$finalShowResponse['status_code']}\n";
            echo "Response: " . json_encode($finalShowResponse['data'], JSON_PRETTY_PRINT) . "\n\n";
        }

        // Investigation 6: Edge cases and special characters
        echo "🔍 Investigation 6: Edge cases and special characters\n";
        
        // Test exactly 1 character
        $response6a = $this->makeRequest('POST', '/comments', [
            'content' => 'A',
            'commentable_type' => 'Issue',
            'commentable_id' => $issueId
        ]);
        echo "1-character content:\n";
        echo "Status: {$response6a['status_code']}\n\n";
        
        // Test exactly 2000 characters
        $content2000 = str_repeat('A', 2000);
        $response6b = $this->makeRequest('POST', '/comments', [
            'content' => $content2000,
            'commentable_type' => 'Issue',
            'commentable_id' => $issueId
        ]);
        echo "2000-character content:\n";
        echo "Status: {$response6b['status_code']}\n\n";
        
        // Test HTML/XSS attempt
        $response6c = $this->makeRequest('POST', '/comments', [
            'content' => '<script>alert("XSS")</script>',
            'commentable_type' => 'Issue',
            'commentable_id' => $issueId
        ]);
        echo "HTML/XSS content:\n";
        echo "Status: {$response6c['status_code']}\n";
        echo "Response: " . json_encode($response6c['data'], JSON_PRETTY_PRINT) . "\n\n";

        // Investigation 7: Different content types
        echo "🔍 Investigation 7: Different content types (JSON vs Form)\n";
        
        // JSON request
        $jsonResponse = $this->makeRequest('POST', '/comments', [
            'content' => 'JSON content test',
            'commentable_type' => 'Issue',
            'commentable_id' => $issueId
        ], ['Content-Type: application/json']);
        
        echo "JSON Request:\n";
        echo "Status: {$jsonResponse['status_code']}\n\n";
        
        // Form request
        $formResponse = $this->makeRequest('POST', '/comments', [
            'content' => 'Form content test',
            'commentable_type' => 'Issue',
            'commentable_id' => $issueId
        ], ['Content-Type: application/x-www-form-urlencoded']);
        
        echo "Form Request:\n";
        echo "Status: {$formResponse['status_code']}\n\n";
    }

    private function printStructure($data, $indent = 0)
    {
        $prefix = str_repeat("  ", $indent);
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    echo "{$prefix}{$key}:\n";
                    $this->printStructure($value, $indent + 1);
                } else {
                    $type = gettype($value);
                    $preview = is_string($value) && strlen($value) > 50 ? substr($value, 0, 50) . "..." : $value;
                    echo "{$prefix}{$key}: ({$type}) {$preview}\n";
                }
            }
        }
    }

    private function getTestIssueId()
    {
        // Try to get an existing issue first
        $response = $this->makeRequest('GET', '/issues');
        
        if ($response['status_code'] === 200 && !empty($response['data']['data']['issues']['data'])) {
            $issueId = $response['data']['data']['issues']['data'][0]['id'];
            echo "✓ Using existing issue ID: {$issueId}\n\n";
            return $issueId;
        }
        
        // Create a new issue if none exist
        $createResponse = $this->makeRequest('POST', '/issues', [
            'title' => 'Test Issue for Comments Investigation',
            'description' => 'Created for detailed comments API testing',
            'type' => 'bug',
            'severity' => 'low',
            'priority' => 'low'
        ]);
        
        if ($createResponse['status_code'] === 201 && isset($createResponse['data']['data']['issue']['id'])) {
            $issueId = $createResponse['data']['data']['issue']['id'];
            echo "✓ Created new issue ID: {$issueId}\n\n";
            return $issueId;
        }
        
        throw new Exception("Could not get or create a test issue");
    }
}

// Run the detailed investigation
try {
    $tester = new DetailedCommentsApiTester();
    $tester->investigateIssues();
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}