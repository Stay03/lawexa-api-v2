<?php

namespace App\Console\Commands;

use App\Services\DeviceDetectionService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TestBotDetection extends Command
{
    protected $signature = 'bot:test';
    protected $description = 'Test bot detection functionality';

    public function handle(DeviceDetectionService $service): int
    {
        $this->info('🤖 Testing Bot Detection Logic');
        $this->info('==============================');
        $this->newLine();

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

        $passedTests = 0;
        $totalTests = count($testCases);

        foreach ($testCases as $index => $testCase) {
            $this->info(sprintf('%d. Testing: %s', $index + 1, $testCase['type']));
            $this->line(sprintf('   User Agent: %s...', substr($testCase['user_agent'], 0, 60)));
            
            // Create a mock request
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_USER_AGENT' => $testCase['user_agent'],
                'REMOTE_ADDR' => '127.0.0.1'
            ]);
            
            $isBot = $service->isBot($request);
            $expected = $testCase['expected'];
            
            if ($isBot === $expected) {
                $this->info(sprintf('   ✅ PASSED: Bot detection = %s (expected %s)', 
                    $isBot ? 'true' : 'false', 
                    $expected ? 'true' : 'false'
                ));
                $passedTests++;
                
                // If it's a bot, show additional info
                if ($isBot) {
                    $botInfo = $service->getBotInfo($request);
                    $this->line(sprintf('   📊 Bot Name: %s', $botInfo['bot_name'] ?? 'Unknown'));
                    $this->line(sprintf('   🔍 Search Engine: %s', $botInfo['is_search_engine'] ? 'Yes' : 'No'));
                    $this->line(sprintf('   📱 Social Media: %s', $botInfo['is_social_media'] ? 'Yes' : 'No'));
                }
            } else {
                $this->error(sprintf('   ❌ FAILED: Bot detection = %s (expected %s)', 
                    $isBot ? 'true' : 'false', 
                    $expected ? 'true' : 'false'
                ));
            }
            
            $this->newLine();
        }

        $this->info('📈 Test Results:');
        $this->info('================');
        $this->info(sprintf('Passed: %d/%d tests', $passedTests, $totalTests));
        $this->info(sprintf('Success Rate: %.1f%%', ($passedTests / $totalTests) * 100));

        if ($passedTests === $totalTests) {
            $this->info('');
            $this->info('🎉 All tests passed! Bot detection is working correctly.');
        } else {
            $this->newLine();
            $this->warn(sprintf('⚠️  %d test(s) failed. Please check the configuration.', $totalTests - $passedTests));
        }

        $this->newLine();
        $this->info('📝 Configuration Status:');
        $this->info('========================');
        
        $this->info('✅ Bot detection enabled: ' . (config('bot-detection.enabled') ? 'Yes' : 'No'));
        $this->info('✅ Bot patterns configured: ' . count(config('bot-detection.bot_patterns', [])) . ' patterns');
        $this->info('✅ Content filtering enabled: ' . (config('bot-detection.bot_access.filter_sensitive_content') ? 'Yes' : 'No'));
        $this->info('✅ Cooldown skip for bots: ' . (config('bot-detection.bot_access.skip_cooldown') ? 'Yes' : 'No'));

        $this->newLine();
        $this->info('🚀 Next Steps:');
        $this->info('==============');
        $this->info('1. Test API endpoints with: php artisan bot:test-endpoints');
        $this->info('2. Verify content filtering for sensitive data');
        $this->info('3. Check view tracking and guest user creation');
        $this->info('4. Monitor bot detection logs if enabled');

        return $passedTests === $totalTests ? 0 : 1;
    }
}