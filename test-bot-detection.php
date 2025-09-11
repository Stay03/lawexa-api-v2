<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\DeviceDetectionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

echo "🤖 Testing Bot Detection Logic\n";
echo "==============================\n\n";

// Test user agents
$testCases = [
    // Bots that should be detected
    [
        'user_agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
        'expected' => true,
        'type' => 'Google Bot'
    ],
    [
        'user_agent' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        'expected' => true,
        'type' => 'Bing Bot'
    ],
    [
        'user_agent' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        'expected' => true,
        'type' => 'Facebook Bot'
    ],
    [
        'user_agent' => 'Twitterbot/1.0',
        'expected' => true,
        'type' => 'Twitter Bot'
    ],
    [
        'user_agent' => 'LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com/)',
        'expected' => true,
        'type' => 'LinkedIn Bot'
    ],
    [
        'user_agent' => 'WhatsApp/2.19.81',
        'expected' => true,
        'type' => 'WhatsApp Bot'
    ],
    [
        'user_agent' => 'AhrefsBot/7.0; +http://ahrefs.com/robot/',
        'expected' => true,
        'type' => 'Ahrefs Bot'
    ],
    
    // Human browsers that should NOT be detected as bots
    [
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        'expected' => false,
        'type' => 'Chrome Browser'
    ],
    [
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'expected' => false,
        'type' => 'Chrome on Mac'
    ],
    [
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'expected' => false,
        'type' => 'Firefox Browser'
    ],
    [
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
        'expected' => false,
        'type' => 'Safari on iPhone'
    ],
];

$service = new DeviceDetectionService();
$passedTests = 0;
$totalTests = count($testCases);

foreach ($testCases as $index => $testCase) {
    echo sprintf("%d. Testing: %s\n", $index + 1, $testCase['type']);
    echo sprintf("   User Agent: %s\n", substr($testCase['user_agent'], 0, 80) . '...');
    
    // Create a mock request
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => $testCase['user_agent'],
        'REMOTE_ADDR' => '127.0.0.1'
    ]);
    
    $isBot = $service->isBot($request);
    $expected = $testCase['expected'];
    
    if ($isBot === $expected) {
        echo sprintf("   ✅ PASSED: Bot detection = %s (expected %s)\n", 
            $isBot ? 'true' : 'false', 
            $expected ? 'true' : 'false'
        );
        $passedTests++;
        
        // If it's a bot, show additional info
        if ($isBot) {
            $botInfo = $service->getBotInfo($request);
            echo sprintf("   📊 Bot Name: %s\n", $botInfo['bot_name'] ?? 'Unknown');
            echo sprintf("   🔍 Search Engine: %s\n", $botInfo['is_search_engine'] ? 'Yes' : 'No');
            echo sprintf("   📱 Social Media: %s\n", $botInfo['is_social_media'] ? 'Yes' : 'No');
        }
    } else {
        echo sprintf("   ❌ FAILED: Bot detection = %s (expected %s)\n", 
            $isBot ? 'true' : 'false', 
            $expected ? 'true' : 'false'
        );
    }
    
    echo "\n";
}

echo "📈 Test Results:\n";
echo "================\n";
echo sprintf("Passed: %d/%d tests\n", $passedTests, $totalTests);
echo sprintf("Success Rate: %.1f%%\n", ($passedTests / $totalTests) * 100);

if ($passedTests === $totalTests) {
    echo "\n🎉 All tests passed! Bot detection is working correctly.\n";
} else {
    echo sprintf("\n⚠️  %d test(s) failed. Please check the configuration.\n", $totalTests - $passedTests);
}

echo "\n📝 Configuration Status:\n";
echo "========================\n";

// Check configuration without loading Laravel app
$configPath = __DIR__ . '/config/bot-detection.php';
if (file_exists($configPath)) {
    echo "✅ Bot detection config file exists\n";
    
    $config = include $configPath;
    echo sprintf("✅ Bot detection enabled: %s\n", $config['enabled'] ? 'Yes' : 'No');
    echo sprintf("✅ Bot patterns configured: %d patterns\n", count($config['bot_patterns']));
    echo sprintf("✅ Content filtering enabled: %s\n", $config['bot_access']['filter_sensitive_content'] ? 'Yes' : 'No');
    echo sprintf("✅ Cooldown skip for bots: %s\n", $config['bot_access']['skip_cooldown'] ? 'Yes' : 'No');
} else {
    echo "❌ Bot detection config file not found\n";
}

echo "\n🚀 Next Steps:\n";
echo "==============\n";
echo "1. Test API endpoints with bot user agents\n";
echo "2. Verify content filtering for sensitive data\n";
echo "3. Check view tracking and guest user creation\n";
echo "4. Monitor bot detection logs if enabled\n";