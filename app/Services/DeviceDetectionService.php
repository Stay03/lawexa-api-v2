<?php

namespace App\Services;

use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Log;

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
}