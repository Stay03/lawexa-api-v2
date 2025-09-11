<?php

namespace App\Services;

use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class DeviceDetectionService
{
    public function detectDevice(string $userAgent, string $ipAddress = null): array
    {
        try {
            $agent = new Agent();
            $agent->setUserAgent($userAgent);
            
            return [
                'device_type' => $this->getDeviceType($agent),
                'device_platform' => $this->getPlatform($agent),
                'device_browser' => $this->getBrowser($agent),
            ];
        } catch (\Exception $e) {
            Log::warning('Device detection failed', [
                'user_agent' => $userAgent,
                'error' => $e->getMessage()
            ]);
            
            return [
                'device_type' => null,
                'device_platform' => null,
                'device_browser' => null,
            ];
        }
    }
    
    private function getDeviceType(Agent $agent): ?string
    {
        if ($agent->isRobot()) {
            return 'robot';
        }
        
        if ($agent->isMobile()) {
            return $agent->isTablet() ? 'tablet' : 'mobile';
        }
        
        if ($agent->isDesktop()) {
            return 'desktop';
        }
        
        return 'unknown';
    }
    
    private function getPlatform(Agent $agent): ?string
    {
        $platform = $agent->platform();
        
        // Limit platform string length and handle null
        if (!$platform) {
            return null;
        }
        
        return strlen($platform) > 50 ? substr($platform, 0, 50) : $platform;
    }
    
    private function getBrowser(Agent $agent): ?string
    {
        $browser = $agent->browser();
        
        // Limit browser string length and handle null
        if (!$browser) {
            return null;
        }
        
        return strlen($browser) > 50 ? substr($browser, 0, 50) : $browser;
    }
    
    /**
     * Get a more detailed device type classification
     */
    public function getDetailedDeviceInfo(string $userAgent): array
    {
        $agent = new Agent();
        $agent->setUserAgent($userAgent);
        
        return [
            'is_mobile' => $agent->isMobile(),
            'is_tablet' => $agent->isTablet(),
            'is_desktop' => $agent->isDesktop(),
            'is_phone' => $agent->isPhone(),
            'is_robot' => $agent->isRobot(),
            'device_name' => $agent->device(),
            'platform' => $agent->platform(),
            'platform_version' => $agent->version($agent->platform()),
            'browser' => $agent->browser(),
            'browser_version' => $agent->version($agent->browser()),
            'languages' => $agent->languages(),
        ];
    }

    /**
     * Check if the request is from a bot/crawler
     */
    public function isBot(Request $request): bool
    {
        if (!config('bot-detection.enabled', true)) {
            return false;
        }

        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();

        // Skip detection if no user agent
        if (empty($userAgent)) {
            return false;
        }

        // Check excluded IPs
        $excludedIps = config('bot-detection.detection_rules.exclude_ips', []);
        if (in_array($ipAddress, $excludedIps)) {
            return false;
        }

        // Check forced bot IPs
        $forceBotIps = config('bot-detection.detection_rules.force_bot_ips', []);
        if (in_array($ipAddress, $forceBotIps)) {
            return true;
        }

        // Check using jenssegers/agent built-in robot detection
        $agent = new Agent();
        $agent->setUserAgent($userAgent);
        if ($agent->isRobot()) {
            return true;
        }

        // Check against configured bot patterns
        $botPatterns = config('bot-detection.bot_patterns', []);
        $userAgentLower = strtolower($userAgent);
        
        foreach ($botPatterns as $pattern) {
            if (strpos($userAgentLower, strtolower($pattern)) !== false) {
                return true;
            }
        }

        // Check bot headers if enabled
        if (config('bot-detection.detection_rules.check_headers', true)) {
            $botHeaders = config('bot-detection.detection_rules.bot_headers', []);
            foreach ($botHeaders as $header) {
                if ($request->hasHeader($header)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get bot information from request
     */
    public function getBotInfo(Request $request): array
    {
        $userAgent = $request->userAgent() ?? '';
        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        return [
            'is_bot' => $this->isBot($request),
            'user_agent' => $userAgent,
            'bot_name' => $this->identifyBot($userAgent),
            'is_search_engine' => $this->isSearchEngineBot($userAgent),
            'is_social_media' => $this->isSocialMediaBot($userAgent),
            'robot_name' => $agent->robot(),
        ];
    }

    /**
     * Identify specific bot type
     */
    private function identifyBot(string $userAgent): ?string
    {
        $userAgentLower = strtolower($userAgent);
        
        $knownBots = [
            'googlebot' => 'Google Bot',
            'bingbot' => 'Bing Bot',
            'yandexbot' => 'Yandex Bot',
            'facebookexternalhit' => 'Facebook Bot',
            'twitterbot' => 'Twitter Bot',
            'linkedinbot' => 'LinkedIn Bot',
            'whatsapp' => 'WhatsApp Bot',
            'slackbot' => 'Slack Bot',
            'ahrefsbot' => 'Ahrefs Bot',
            'semrushbot' => 'SEMrush Bot',
            'mj12bot' => 'Majestic Bot',
        ];

        foreach ($knownBots as $pattern => $name) {
            if (strpos($userAgentLower, $pattern) !== false) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Check if it's a search engine bot
     */
    private function isSearchEngineBot(string $userAgent): bool
    {
        $searchEnginePatterns = [
            'googlebot', 'bingbot', 'yandexbot', 'duckduckbot', 
            'baiduspider', 'sogou', 'exabot'
        ];
        
        $userAgentLower = strtolower($userAgent);
        
        foreach ($searchEnginePatterns as $pattern) {
            if (strpos($userAgentLower, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if it's a social media bot
     */
    private function isSocialMediaBot(string $userAgent): bool
    {
        $socialMediaPatterns = [
            'facebookexternalhit', 'twitterbot', 'linkedinbot', 
            'whatsapp', 'slackbot', 'telegrambot', 'discordbot'
        ];
        
        $userAgentLower = strtolower($userAgent);
        
        foreach ($socialMediaPatterns as $pattern) {
            if (strpos($userAgentLower, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}