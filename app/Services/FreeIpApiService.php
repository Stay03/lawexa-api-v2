<?php

namespace App\Services;

use App\Contracts\IpGeolocationServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FreeIpApiService implements IpGeolocationServiceInterface
{
    private const BASE_URL = 'https://free.freeipapi.com/api/json/';
    private const RATE_LIMIT_KEY = 'free_ip_api_requests';
    private const MAX_REQUESTS_PER_MINUTE = 100; // Conservative estimate for free tier
    
    public function getLocation(string $ipAddress): ?array
    {
        if (!config('app.freeipapi_enabled', true)) {
            return null;
        }
        
        if (!$this->canMakeRequest()) {
            Log::warning('FreeIPAPI rate limit exceeded');
            return null;
        }
        
        try {
            $this->incrementRequestCount();
            
            $headers = [];
            $apiKey = config('app.freeipapi_api_key');
            if ($apiKey) {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }
            
            $response = Http::timeout(10)
                ->withoutVerifying() // Disable SSL verification for development
                ->withHeaders($headers)
                ->get(self::BASE_URL . $ipAddress);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // FreeIPAPI doesn't have a status field like IP-API
                // Consider successful if we have at least country data
                if (isset($data['countryName']) && !empty($data['countryName'])) {
                    // Extract first timezone from array if available
                    $timezone = null;
                    if (isset($data['timeZones']) && is_array($data['timeZones']) && !empty($data['timeZones'])) {
                        $timezone = $data['timeZones'][0];
                    }
                    
                    return [
                        'status' => 'success',
                        'country' => $data['countryName'] ?? null,
                        'country_code' => $data['countryCode'] ?? null,
                        'continent' => $data['continent'] ?? null,
                        'continent_code' => $data['continentCode'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'city' => $data['cityName'] ?? null,
                        'timezone' => $timezone,
                    ];
                } else {
                    Log::warning('FreeIPAPI returned incomplete data', [
                        'ip' => $ipAddress,
                        'response' => $data
                    ]);
                }
            } else {
                Log::warning('FreeIPAPI HTTP error', [
                    'ip' => $ipAddress,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('FreeIPAPI service error', [
                'ip' => $ipAddress,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    public function isServiceAvailable(): bool
    {
        return config('app.freeipapi_enabled', true) && $this->canMakeRequest();
    }
    
    public function getProviderName(): string
    {
        return 'freeipapi.com';
    }
    
    private function canMakeRequest(): bool
    {
        $currentCount = Cache::get(self::RATE_LIMIT_KEY, 0);
        return $currentCount < self::MAX_REQUESTS_PER_MINUTE;
    }
    
    private function incrementRequestCount(): void
    {
        $currentCount = Cache::get(self::RATE_LIMIT_KEY, 0);
        Cache::put(self::RATE_LIMIT_KEY, $currentCount + 1, now()->addMinute());
    }
}