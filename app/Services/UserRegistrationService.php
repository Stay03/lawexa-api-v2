<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserRegistrationService
{
    private IpGeolocationService $ipGeolocationService;
    private DeviceDetectionService $deviceDetectionService;

    public function __construct(
        IpGeolocationService $ipGeolocationService,
        DeviceDetectionService $deviceDetectionService
    ) {
        $this->ipGeolocationService = $ipGeolocationService;
        $this->deviceDetectionService = $deviceDetectionService;
    }

    /**
     * Extract geo and device data from request for user registration
     */
    public function extractRegistrationData(Request $request): array
    {
        try {
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent() ?? '';
            
            // Get geolocation data for the IP address
            $geoData = $this->ipGeolocationService->getLocation($ipAddress);
            
            // Get device data from user agent
            $deviceData = $this->deviceDetectionService->detectDevice($userAgent, $ipAddress);
            
            return [
                'registration_ip_address' => $ipAddress,
                'registration_user_agent' => $userAgent,
                'ip_country' => $geoData['country'] ?? null,
                'ip_country_code' => $geoData['country_code'] ?? null,
                'ip_continent' => $geoData['continent'] ?? null,
                'ip_continent_code' => $geoData['continent_code'] ?? null,
                'ip_region' => $geoData['region'] ?? null,
                'ip_city' => $geoData['city'] ?? null,
                'ip_timezone' => $geoData['timezone'] ?? null,
                'device_type' => $deviceData['device_type'],
                'device_platform' => $deviceData['device_platform'],
                'device_browser' => $deviceData['device_browser'],
            ];
        } catch (\Exception $e) {
            Log::warning('Registration data extraction failed', [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'error' => $e->getMessage()
            ]);
            
            // Return minimal data if extraction fails - registration should still work
            return [
                'registration_ip_address' => $request->ip(),
                'registration_user_agent' => $request->userAgent() ?? '',
                'ip_country' => null,
                'ip_country_code' => null,
                'ip_continent' => null,
                'ip_continent_code' => null,
                'ip_region' => null,
                'ip_city' => null,
                'ip_timezone' => null,
                'device_type' => null,
                'device_platform' => null,
                'device_browser' => null,
            ];
        }
    }

    /**
     * Get formatted location string for display purposes
     */
    public function getFormattedLocation(array $registrationData): ?string
    {
        $parts = array_filter([
            $registrationData['ip_city'] ?? null,
            $registrationData['ip_region'] ?? null,
            $registrationData['ip_country'] ?? null
        ]);
        
        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Get formatted device string for display purposes
     */
    public function getFormattedDevice(array $registrationData): ?string
    {
        $parts = array_filter([
            $registrationData['device_browser'] ?? null,
            $registrationData['device_platform'] ?? null,
            $registrationData['device_type'] ?? null
        ]);
        
        return !empty($parts) ? implode(' on ', $parts) : null;
    }
}