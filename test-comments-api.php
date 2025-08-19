<?php

/**
 * Comprehensive Comments API Testing Script
 * Tests all comments endpoints and edge cases using .env.text configuration
 */

class CommentsApiTester
{
    private $localApiUrl;
    private $localApiToken;
    private $liveApiUrl;
    private $liveApiToken;
    private $testResults = [];
    private $testCount = 0;
    private $passCount = 0;
    private $failCount = 0;
    private $currentApiUrl;
    private $currentApiToken;
    private $environment;

    public function __construct()
    {
        $this->loadCredentials();
    }

    private function loadCredentials()
    {
        $envContent = file_get_contents('.env.text');
        
        // Extract credentials using regex
        preg_match("/API_TOKEN='([^']+)'/", $envContent, $localTokenMatch);
        preg_match("/API_URL='([^']+)'/", $envContent, $localUrlMatch);
        preg_match("/LIVE_API_TOKEN='([^']+)'/", $envContent, $liveTokenMatch);
        preg_match("/LIVE_API_URL='([^']+)'/", $envContent, $liveUrlMatch);
        
        $this->localApiToken = $localTokenMatch[1] ?? null;
        $this->localApiUrl = $localUrlMatch[1] ?? null;
        $this->liveApiToken = $liveTokenMatch[1] ?? null;
        $this->liveApiUrl = $liveUrlMatch[1] ?? null;
        
        if (!$this->localApiToken || !$this->localApiUrl) {
            throw new Exception("Failed to extract local API credentials from .env.text");
        }
        
        echo "✓ Credentials loaded successfully\n";
        echo "  Local API: {$this->localApiUrl}\n";
        echo "  Live API: {$this->liveApiUrl}\n\n";
    }

    public function setEnvironment($environment = 'local')
    {
        $this->environment = $environment;
        if ($environment === 'local') {
            $this->currentApiUrl = $this->localApiUrl;
            $this->currentApiToken = $this->localApiToken;
        } else {
            $this->currentApiUrl = $this->liveApiUrl;
            $this->currentApiToken = $this->liveApiToken;
        }
        echo "🌍 Testing environment: " . strtoupper($environment) . "\n";
        echo "   URL: {$this->currentApiUrl}\n\n";
    }

    private function makeRequest($method, $endpoint, $data = null, $customHeaders = [])
    {
        $url = $this->currentApiUrl . $endpoint;
        
        $headers = array_merge([
            'Authorization: Bearer ' . $this->currentApiToken,
            'Accept: application/json',
            'User-Agent: Comments-API-Tester/1.0'
        ], $customHeaders);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $method
        ]);

        if ($data !== null) {
            if (in_array('Content-Type: application/json', $headers)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("CURL Error: " . $error);
        }

        return [
            'status_code' => $httpCode,
            'body' => $response,
            'data' => json_decode($response, true)
        ];
    }

    private function test($name, $callback, $description = '')
    {
        $this->testCount++;
        echo "🧪 Test #{$this->testCount}: {$name}\n";
        if ($description) {
            echo "   Description: {$description}\n";
        }

        try {
            $result = $callback();
            if ($result === true) {
                $this->passCount++;
                echo "   ✅ PASS\n\n";
                $this->testResults[] = ['name' => $name, 'status' => 'PASS', 'description' => $description];
            } else {
                $this->failCount++;
                echo "   ❌ FAIL: " . ($result ?: 'Test returned false') . "\n\n";
                $this->testResults[] = ['name' => $name, 'status' => 'FAIL', 'error' => $result, 'description' => $description];
            }
        } catch (Exception $e) {
            $this->failCount++;
            echo "   ❌ ERROR: " . $e->getMessage() . "\n\n";
            $this->testResults[] = ['name' => $name, 'status' => 'ERROR', 'error' => $e->getMessage(), 'description' => $description];
        }
    }

    private function assertStatus($response, $expectedStatus, $message = null)
    {
        if ($response['status_code'] !== $expectedStatus) {
            $actualBody = json_encode($response['data'], JSON_PRETTY_PRINT);
            throw new Exception(($message ?: "Expected status {$expectedStatus}, got {$response['status_code']}") . "\nResponse: {$actualBody}");
        }
        return true;
    }

    private function assertResponseStructure($response, $requiredFields)
    {
        $data = $response['data'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        return true;
    }

    public function createTestIssue()
    {
        echo "📝 Creating test issue...\n";
        $response = $this->makeRequest('POST', '/issues', [
            'title' => 'Comments System Test Issue - ' . date('Y-m-d H:i:s'),
            'description' => 'This is a test issue for testing the comments functionality',
            'type' => 'bug',
            'severity' => 'medium',
            'priority' => 'medium'
        ]);

        if ($response['status_code'] === 201 && isset($response['data']['data']['issue']['id'])) {
            $issueId = $response['data']['data']['issue']['id'];
            echo "✓ Test issue created with ID: {$issueId}\n\n";
            return $issueId;
        } else {
            echo "⚠️  Could not create test issue, will attempt to find existing issues\n\n";
            return $this->findExistingIssue();
        }
    }

    public function createTestNote()
    {
        echo "📝 Creating test note...\n";
        $response = $this->makeRequest('POST', '/notes', [
            'title' => 'Comments System Test Note - ' . date('Y-m-d H:i:s'),
            'content' => 'This is a test note for testing the comments functionality',
            'is_private' => false
        ]);

        if ($response['status_code'] === 201 && isset($response['data']['data']['note']['id'])) {
            $noteId = $response['data']['data']['note']['id'];
            echo "✓ Test note created with ID: {$noteId}\n\n";
            return $noteId;
        } else {
            echo "⚠️  Could not create test note, will attempt to find existing notes\n\n";
            return $this->findExistingNote();
        }
    }

    private function findExistingIssue()
    {
        $response = $this->makeRequest('GET', '/issues');
        if ($response['status_code'] === 200 && !empty($response['data']['data']['issues']['data'])) {
            $issueId = $response['data']['data']['issues']['data'][0]['id'];
            echo "✓ Found existing issue with ID: {$issueId}\n\n";
            return $issueId;
        }
        throw new Exception("No issues available for testing");
    }

    private function findExistingNote()
    {
        $response = $this->makeRequest('GET', '/notes');
        if ($response['status_code'] === 200 && !empty($response['data']['data']['notes']['data'])) {
            $noteId = $response['data']['data']['notes']['data'][0]['id'];
            echo "✓ Found existing note with ID: {$noteId}\n\n";
            return $noteId;
        }
        throw new Exception("No notes available for testing");
    }

    public function runAllTests()
    {
        echo "🚀 Starting Comprehensive Comments API Tests\n";
        echo "==========================================\n\n";

        try {
            // Create test data
            $issueId = $this->createTestIssue();
            $noteId = $this->createTestNote();

            // Test 1: List Comments - Valid Request
            $this->test('List Comments - Valid Issue', function() use ($issueId) {
                $response = $this->makeRequest('GET', "/comments?commentable_type=Issue&commentable_id={$issueId}");
                $this->assertStatus($response, 200);
                $this->assertResponseStructure($response, ['status', 'message', 'data']);
                return true;
            }, 'Test valid comment listing for an issue');

            // Test 2: List Comments - Invalid commentable_type
            $this->test('List Comments - Invalid Type', function() use ($issueId) {
                $response = $this->makeRequest('GET', "/comments?commentable_type=InvalidType&commentable_id={$issueId}");
                $this->assertStatus($response, 400);
                return true;
            }, 'Test invalid commentable type handling');

            // Test 3: List Comments - Missing Parameters
            $this->test('List Comments - Missing Parameters', function() {
                $response = $this->makeRequest('GET', '/comments');
                $this->assertStatus($response, 400);
                return true;
            }, 'Test missing required parameters');

            // Test 4: Create Comment - Valid
            $commentId = null;
            $this->test('Create Comment - Valid Issue Comment', function() use ($issueId, &$commentId) {
                $response = $this->makeRequest('POST', '/comments', [
                    'content' => 'This is a test comment for automated testing',
                    'commentable_type' => 'Issue',
                    'commentable_id' => $issueId
                ], ['Content-Type: application/x-www-form-urlencoded']);
                
                $this->assertStatus($response, 201);
                $this->assertResponseStructure($response, ['status', 'message', 'data']);
                
                if (isset($response['data']['data']['comment']['id'])) {
                    $commentId = $response['data']['data']['comment']['id'];
                }
                return true;
            }, 'Test creating a valid comment on an issue');

            // Test 5: Create Comment - Content Too Long
            $this->test('Create Comment - Content Too Long', function() use ($issueId) {
                $longContent = str_repeat('A', 2001); // 2001 characters
                $response = $this->makeRequest('POST', '/comments', [
                    'content' => $longContent,
                    'commentable_type' => 'Issue',
                    'commentable_id' => $issueId
                ]);
                
                $this->assertStatus($response, 422);
                return true;
            }, 'Test content length validation');

            // Test 6: Create Comment - Empty Content
            $this->test('Create Comment - Empty Content', function() use ($issueId) {
                $response = $this->makeRequest('POST', '/comments', [
                    'content' => '',
                    'commentable_type' => 'Issue',
                    'commentable_id' => $issueId
                ]);
                
                $this->assertStatus($response, 422);
                return true;
            }, 'Test empty content validation');

            // Test 7: Create Comment - Non-existent Resource
            $this->test('Create Comment - Non-existent Resource', function() {
                $response = $this->makeRequest('POST', '/comments', [
                    'content' => 'Comment on non-existent resource',
                    'commentable_type' => 'Issue',
                    'commentable_id' => 999999
                ]);
                
                $this->assertStatus($response, 404);
                return true;
            }, 'Test commenting on non-existent resource');

            // Test 8: Create Comment - Invalid Security Type
            $this->test('Create Comment - Security Test Invalid Type', function() use ($issueId) {
                $response = $this->makeRequest('POST', '/comments', [
                    'content' => 'Attempting to comment on User model',
                    'commentable_type' => 'User',
                    'commentable_id' => 1
                ]);
                
                $this->assertStatus($response, 422);
                return true;
            }, 'Test security validation for invalid commentable types');

            // Test 9: Show Comment - Valid
            if ($commentId) {
                $this->test('Show Comment - Valid', function() use ($commentId) {
                    $response = $this->makeRequest('GET', "/comments/{$commentId}");
                    $this->assertStatus($response, 200);
                    $this->assertResponseStructure($response, ['status', 'message', 'data']);
                    return true;
                }, 'Test retrieving a specific comment');
            }

            // Test 10: Show Comment - Non-existent
            $this->test('Show Comment - Non-existent', function() {
                $response = $this->makeRequest('GET', '/comments/999999');
                $this->assertStatus($response, 404);
                return true;
            }, 'Test retrieving non-existent comment');

            // Test 11: Update Comment - Valid (if we have a comment)
            if ($commentId) {
                $this->test('Update Comment - Valid', function() use ($commentId) {
                    $response = $this->makeRequest('PUT', "/comments/{$commentId}", [
                        'content' => 'Updated test comment content'
                    ], ['Content-Type: application/x-www-form-urlencoded']);
                    
                    $this->assertStatus($response, 200);
                    $this->assertResponseStructure($response, ['status', 'message', 'data']);
                    
                    // Check if is_edited flag is set
                    if (!$response['data']['data']['comment']['is_edited']) {
                        throw new Exception('Comment should be marked as edited');
                    }
                    return true;
                }, 'Test updating own comment');
            }

            // Test 12: Create Reply - Valid
            $replyId = null;
            if ($commentId) {
                $this->test('Create Reply - Valid', function() use ($commentId, &$replyId) {
                    $response = $this->makeRequest('POST', "/comments/{$commentId}/reply", [
                        'content' => 'This is a test reply'
                    ], ['Content-Type: application/json']);
                    
                    $this->assertStatus($response, 201);
                    $this->assertResponseStructure($response, ['status', 'message', 'data']);
                    
                    if (isset($response['data']['data']['comment']['id'])) {
                        $replyId = $response['data']['data']['comment']['id'];
                    }
                    return true;
                }, 'Test creating a reply using dedicated endpoint');
            }

            // Test 13: Create Reply - Non-existent Parent
            $this->test('Create Reply - Non-existent Parent', function() {
                $response = $this->makeRequest('POST', '/comments/999999/reply', [
                    'content' => 'Reply to non-existent comment'
                ], ['Content-Type: application/json']);
                
                $this->assertStatus($response, 404);
                return true;
            }, 'Test reply to non-existent comment');

            // Test 14: Comment on Note
            $this->test('Create Comment - Valid Note Comment', function() use ($noteId) {
                $response = $this->makeRequest('POST', '/comments', [
                    'content' => 'This is a test comment on a note',
                    'commentable_type' => 'Note',
                    'commentable_id' => $noteId
                ]);
                
                $this->assertStatus($response, 201);
                $this->assertResponseStructure($response, ['status', 'message', 'data']);
                return true;
            }, 'Test creating a comment on a note');

            // Test 15: Full Format commentable_type
            $this->test('List Comments - Full Format Type', function() use ($issueId) {
                $response = $this->makeRequest('GET', "/comments?commentable_type=App%5CModels%5CIssue&commentable_id={$issueId}");
                $this->assertStatus($response, 200);
                return true;
            }, 'Test full format commentable type (App\\Models\\Issue)');

            // Test 16: Authentication - No Token
            $this->test('Authentication - No Token', function() use ($issueId) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $this->currentApiUrl . "/comments?commentable_type=Issue&commentable_id={$issueId}",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => ['Accept: application/json'],
                    CURLOPT_TIMEOUT => 30,
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 401) {
                    throw new Exception("Expected 401, got {$httpCode}");
                }
                return true;
            }, 'Test request without authentication token');

            // Test 17: Content Validation - Exactly 2000 characters
            $this->test('Create Comment - Max Length Content', function() use ($issueId) {
                $maxContent = str_repeat('A', 2000); // Exactly 2000 characters
                $response = $this->makeRequest('POST', '/comments', [
                    'content' => $maxContent,
                    'commentable_type' => 'Issue',
                    'commentable_id' => $issueId
                ]);
                
                $this->assertStatus($response, 201);
                return true;
            }, 'Test maximum allowed content length (2000 chars)');

            // Test 18: Unicode Content
            $this->test('Create Comment - Unicode Content', function() use ($issueId) {
                $response = $this->makeRequest('POST', '/comments', [
                    'content' => 'Test with unicode: 🚀 ✅ ❌ 中文 العربية',
                    'commentable_type' => 'Issue',
                    'commentable_id' => $issueId
                ]);
                
                $this->assertStatus($response, 201);
                return true;
            }, 'Test unicode and special characters in content');

            // Test 19: Pagination
            $this->test('List Comments - Pagination', function() use ($issueId) {
                $response = $this->makeRequest('GET', "/comments?commentable_type=Issue&commentable_id={$issueId}&per_page=5&page=1");
                $this->assertStatus($response, 200);
                
                // Check pagination metadata
                $data = $response['data']['data']['comments'];
                if (!isset($data['meta']) || !isset($data['links'])) {
                    throw new Exception('Pagination metadata missing');
                }
                return true;
            }, 'Test pagination parameters');

            // Test 20: Delete Comment - Valid (if we have a reply)
            if ($replyId) {
                $this->test('Delete Comment - Valid', function() use ($replyId) {
                    $response = $this->makeRequest('DELETE', "/comments/{$replyId}");
                    $this->assertStatus($response, 200);
                    return true;
                }, 'Test deleting own comment');
            }

        } catch (Exception $e) {
            echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n\n";
        }

        $this->printSummary();
    }

    private function printSummary()
    {
        echo "\n🏁 TEST SUMMARY ({$this->environment})\n";
        echo "=====================================\n";
        echo "Total Tests: {$this->testCount}\n";
        echo "✅ Passed: {$this->passCount}\n";
        echo "❌ Failed: {$this->failCount}\n";
        $successRate = $this->testCount > 0 ? round(($this->passCount / $this->testCount) * 100, 2) : 0;
        echo "Success Rate: {$successRate}%\n\n";

        if ($this->failCount > 0) {
            echo "FAILED TESTS:\n";
            echo "=============\n";
            foreach ($this->testResults as $result) {
                if ($result['status'] !== 'PASS') {
                    echo "❌ {$result['name']}\n";
                    if (isset($result['error'])) {
                        echo "   Error: {$result['error']}\n";
                    }
                    echo "\n";
                }
            }
        }
    }

    public function runTestsOnBothEnvironments()
    {
        // Test on live environment first since local may not be running
        $this->setEnvironment('live');
        $this->runAllTests();

        // Store live results
        $liveResults = [
            'count' => $this->testCount,
            'pass' => $this->passCount,
            'fail' => $this->failCount,
            'results' => $this->testResults
        ];

        echo "\n🎯 FINAL SUMMARY\n";
        echo "================\n";
        echo "LIVE ENVIRONMENT:\n";
        echo "  Tests: {$liveResults['count']}, Passed: {$liveResults['pass']}, Failed: {$liveResults['fail']}\n";
        echo "\n";
    }
}

// Run the tests
try {
    $tester = new CommentsApiTester();
    $tester->runTestsOnBothEnvironments();
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}