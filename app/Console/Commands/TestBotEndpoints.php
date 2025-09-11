<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestBotEndpoints extends Command
{
    protected $signature = 'bot:test-endpoints {--url=http://localhost:8000}';
    protected $description = 'Test bot detection on API endpoints';

    public function handle(): int
    {
        $baseUrl = $this->option('url');
        
        $this->info('🤖 Testing Bot Detection API Endpoints');
        $this->info('======================================');
        $this->info('Base URL: ' . $baseUrl);
        $this->newLine();

        // Test different bot user agents
        $botAgents = [
            'Googlebot/2.1 (+http://www.google.com/bot.html)',
            'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
            'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
            'Twitterbot/1.0',
            'WhatsApp/2.19.81',
        ];

        // Test endpoints - use actual IDs/slugs from your database
        $endpoints = [
            '/api/cases/sample-case-slug',  // Replace with actual case slug
            '/api/statutes/sample-statute-slug', // Replace with actual statute slug
            '/api/notes/1', // Replace with actual note ID
        ];

        $this->info('Testing Bot User Agents:');
        $this->info('----------------------');

        foreach ($botAgents as $index => $botAgent) {
            $this->newLine();
            $this->info(sprintf('Testing with: %s', substr($botAgent, 0, 50) . '...'));
            $this->line('----------------------------------------');
            
            foreach ($endpoints as $endpoint) {
                $this->line('  Testing endpoint: ' . $endpoint);
                
                try {
                    $response = Http::withHeaders([
                        'User-Agent' => $botAgent,
                        'Accept' => 'application/json',
                    ])->timeout(10)->get($baseUrl . $endpoint);
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        
                        // Check if response contains bot indicator
                        if (isset($data['data']) && is_array($data['data'])) {
                            $responseData = $data['data'];
                            
                            if (isset($responseData['case'])) {
                                $caseData = $responseData['case'];
                                $this->checkBotResponse($caseData, 'case');
                            } elseif (isset($responseData['statute'])) {
                                $statuteData = $responseData['statute'];
                                $this->checkBotResponse($statuteData, 'statute');
                            } elseif (isset($responseData['note'])) {
                                $noteData = $responseData['note'];
                                $this->checkBotResponse($noteData, 'note');
                            } else {
                                // Direct data response
                                $this->checkBotResponse($responseData, 'unknown');
                            }
                        } else {
                            $this->warn('    ⚠️  Unexpected response structure');
                        }
                    } else {
                        $this->error('    ❌ Request failed: ' . $response->status());
                    }
                } catch (\Exception $e) {
                    $this->error('    ❌ Exception: ' . $e->getMessage());
                }
            }
        }

        $this->newLine();
        $this->info('Testing Human User Agent:');
        $this->info('------------------------');

        $humanAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';

        foreach ($endpoints as $endpoint) {
            $this->line('  Testing endpoint: ' . $endpoint);
            
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $humanAgent,
                    'Accept' => 'application/json',
                ])->timeout(10)->get($baseUrl . $endpoint);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['data']) && is_array($data['data'])) {
                        $responseData = $data['data'];
                        
                        if (isset($responseData['case'])) {
                            $caseData = $responseData['case'];
                            $this->checkHumanResponse($caseData, 'case');
                        } elseif (isset($responseData['statute'])) {
                            $statuteData = $responseData['statute'];
                            $this->checkHumanResponse($statuteData, 'statute');
                        } elseif (isset($responseData['note'])) {
                            $noteData = $responseData['note'];
                            $this->checkHumanResponse($noteData, 'note');
                        } else {
                            $this->checkHumanResponse($responseData, 'unknown');
                        }
                    } else {
                        $this->warn('    ⚠️  Unexpected response structure');
                    }
                } else {
                    $this->error('    ❌ Request failed: ' . $response->status());
                }
            } catch (\Exception $e) {
                $this->error('    ❌ Exception: ' . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Testing Complete! 🎉');
        $this->info('===================');

        return 0;
    }

    private function checkBotResponse(array $data, string $type): void
    {
        // Check if response contains bot indicator
        if (isset($data['isBot']) && $data['isBot'] === true) {
            $this->info('    ✅ Bot detected successfully');
            
            // Extract bot info if present
            if (isset($data['bot_info']['bot_name'])) {
                $this->line('    📊 Bot identified as: ' . $data['bot_info']['bot_name']);
            }
            
            // For case endpoints, check if sensitive fields are filtered
            if ($type === 'case') {
                if (isset($data['report'])) {
                    $this->warn('    ⚠️  WARNING: Report field present in bot response');
                } else {
                    $this->info('    ✅ Sensitive content filtered (report field removed)');
                }
                
                if (isset($data['case_report_text'])) {
                    $this->warn('    ⚠️  WARNING: Case report text present in bot response');
                } else {
                    $this->info('    ✅ Sensitive content filtered (case_report_text field removed)');
                }
            }
        } else {
            $this->error('    ❌ Bot detection failed');
        }
    }

    private function checkHumanResponse(array $data, string $type): void
    {
        // Check if response does NOT contain bot indicator
        if (isset($data['isBot']) && $data['isBot'] === true) {
            $this->error('    ❌ Human incorrectly detected as bot');
        } else {
            $this->info('    ✅ Human user agent handled correctly');
            
            // For case endpoints, check if full content is available
            if ($type === 'case') {
                if (isset($data['report'])) {
                    $this->info('    ✅ Full content available (report field present)');
                } else {
                    $this->warn('    ⚠️  WARNING: Report field missing in human response');
                }
            }
        }
    }
}