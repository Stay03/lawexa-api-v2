<?php

namespace App\Services;

use App\Contracts\IpGeolocationServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IpApiService implements IpGeolocationServiceInterface
{
    private const BASE_URL = 'http://ip-api.com/json/';
    private const RATE_LIMIT_KEY = 'ip_api_requests';
    private const MAX_REQUESTS_PER_MINUTE = 45;
    
    public function getLocation(string $ipAddress): ?array
    {
        if (!$this->canMakeRequest()) {
            Log::warning('IP-API rate limit exceeded');
            return null;
        }
        
        try {
            $this->incrementRequestCount();
            
            $response = Http::timeout(10)->get(self::BASE_URL . $ipAddress, [
                'fields' => 'status,message,country,countryCode,region,city,timezone,continent,continentCode'
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'status' => $data['status'],
                        'country' => $data['country'] ?? null,
                        'country_code' => $data['countryCode'] ?? null,
                        'continent' => $data['continent'] ?? null,
                        'continent_code' => $data['continentCode'] ?? null,
                        'region' => $data['region'] ?? null,
                        'city' => $data['city'] ?? null,
                        'timezone' => $data['timezone'] ?? null,
                    ];
                } else {
                    Log::warning('IP-API returned error', [
                        'ip' => $ipAddress,
                        'message' => $data['message'] ?? 'Unknown error'
                    ]);
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('IP-API service error', [
                'ip' => $ipAddress,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    public function isServiceAvailable(): bool
    {
        return $this->canMakeRequest();
    }
    
    public function getProviderName(): string
    {
        return 'ip-api.com';
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