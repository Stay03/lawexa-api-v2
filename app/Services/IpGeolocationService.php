<?php

namespace App\Services;

use App\Contracts\IpGeolocationServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IpGeolocationService
{
    private array $providers = [];
    
    public function __construct()
    {
        $this->providers = [
            app(IpApiService::class),
            app(FreeIpApiService::class),
        ];
    }
    
    public function getLocation(string $ipAddress): ?array
    {
        if (!config('app.ip_geolocation_enabled', true)) {
            return null;
        }
        
        if (empty($ipAddress) || $ipAddress === '127.0.0.1' || $ipAddress === '::1') {
            return null;
        }
        
        $cacheKey = "geolocation:" . md5($ipAddress);
        $cacheHours = (int) config('app.ip_geolocation_cache_hours', 24);
        
        return Cache::remember($cacheKey, now()->addHours($cacheHours), function () use ($ipAddress) {
            foreach ($this->providers as $provider) {
                try {
                    if ($provider->isServiceAvailable()) {
                        $location = $provider->getLocation($ipAddress);
                        if ($location && $this->isValidLocationData($location)) {
                            Log::info('Geolocation successful', [
                                'provider' => $provider->getProviderName(),
                                'ip' => $ipAddress,
                                'country' => $location['country'] ?? null
                            ]);
                            return $location;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Geolocation provider failed', [
                        'provider' => $provider->getProviderName(),
                        'ip' => $ipAddress,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }
            
            Log::info('No geolocation data available', ['ip' => $ipAddress]);
            return null;
        });
    }
    
    private function isValidLocationData(?array $data): bool
    {
        if (!$data) {
            return false;
        }
        
        // Check if we have the status field (IP-API format) or just validate country presence (FreeIPAPI format)
        if (isset($data['status'])) {
            // IP-API format validation
            return $data['status'] === 'success' && !empty($data['country']);
        } else {
            // FreeIPAPI format validation - just ensure we have country data
            return !empty($data['country']);
        }
    }
}