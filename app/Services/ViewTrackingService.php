<?php

namespace App\Services;

use App\Models\ModelView;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ViewTrackingService
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

    public function trackView(Model $model, Request $request): void
    {
        $viewData = $this->extractViewData($model, $request);
        
        // Use database transaction for atomic view tracking
        DB::transaction(function () use ($viewData, $request) {
            // Double-check cooldown within transaction to prevent race conditions
            if ($this->canTrackView($viewData, $request)) {
                $this->recordView($viewData);
            }
        });
    }

    public function canTrackView(array $viewData, Request $request = null): bool
    {
        // Check if request is from a bot and bot cooldown skip is enabled
        if ($request && $request->attributes->get('is_bot', false)) {
            $skipCooldown = config('bot-detection.bot_access.skip_cooldown', true);
            if ($skipCooldown) {
                // For bots, skip cooldown but still check guest limits
                return ModelView::canViewBot(
                    $viewData['viewable_type'],
                    $viewData['viewable_id'],
                    $viewData['user_id']
                );
            }
        }

        // Check if view can be tracked (includes cooldown and guest limit checks)
        return ModelView::canView(
            $viewData['viewable_type'],
            $viewData['viewable_id'],
            $viewData['user_id'],
            $viewData['session_id'],
            $viewData['ip_address'],
            $viewData['user_agent_hash']
        );
    }

    public function recordView(array $viewData): ?ModelView
    {
        return ModelView::recordView(
            $viewData['viewable_type'],
            $viewData['viewable_id'],
            $viewData['user_id'],
            $viewData['session_id'],
            $viewData['ip_address'],
            $viewData['user_agent_hash'],
            $viewData['user_agent'],
            $viewData['ip_country'],
            $viewData['ip_country_code'],
            $viewData['ip_continent'],
            $viewData['ip_continent_code'],
            $viewData['ip_region'],
            $viewData['ip_city'],
            $viewData['ip_timezone'],
            $viewData['device_type'],
            $viewData['device_platform'],
            $viewData['device_browser'],
            $viewData['is_bot'],
            $viewData['bot_name'],
            $viewData['is_search_engine'],
            $viewData['is_social_media'],
            $viewData['search_query'] ?? null,
            $viewData['is_from_search'] ?? false
        );
    }

    private function extractViewData(Model $model, Request $request): array
    {
        $user = $request->user();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent() ?? '';
        
        // Get geolocation data for the IP address
        $geoData = $this->ipGeolocationService->getLocation($ipAddress);
        
        // Get device data from user agent
        $deviceData = $this->deviceDetectionService->detectDevice($userAgent, $ipAddress);
        
        // Get bot information from request attributes (set by BotDetectionMiddleware)
        $isBot = $request->attributes->get('is_bot', false);
        $botInfo = $request->attributes->get('bot_info', []);

        // Extract search query from request
        $searchQuery = $request->query('search_query') ?? $request->query('q');
        if ($searchQuery !== null) {
            // URL decode (Laravel does this automatically, but being explicit)
            $searchQuery = urldecode($searchQuery);

            // Trim whitespace
            $searchQuery = trim($searchQuery);

            // Remove control characters and null bytes
            $searchQuery = preg_replace('/[\x00-\x1F\x7F]/u', '', $searchQuery);

            // Truncate to 500 characters
            if (strlen($searchQuery) > 500) {
                $searchQuery = substr($searchQuery, 0, 500);
            }

            // Set to null if empty after sanitization
            if ($searchQuery === '') {
                $searchQuery = null;
            }
        }

        // With guest authentication system, we always have a user (either real user or guest)
        // No need for session-based tracking anymore

        return [
            'viewable_type' => get_class($model),
            'viewable_id' => $model->getKey(),
            'user_id' => $user?->id,
            'session_id' => null, // No longer needed with guest user system
            'ip_address' => $ipAddress,
            'user_agent_hash' => hash('sha256', $userAgent . $ipAddress), // Keep for backward compatibility
            'user_agent' => $userAgent, // Store raw user agent for analytics
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
            // Bot information
            'is_bot' => $isBot,
            'bot_name' => $botInfo['bot_name'] ?? null,
            'is_search_engine' => $botInfo['is_search_engine'] ?? null,
            'is_social_media' => $botInfo['is_social_media'] ?? null,
            // Search tracking
            'search_query' => $searchQuery,
            'is_from_search' => !empty($searchQuery),
        ];
    }

    public function getViewStats(Model $model): array
    {
        $views = $model->views();
        
        return [
            'total_views' => $views->count(),
            'unique_users' => $views->distinct('user_id')->whereNotNull('user_id')->count(),
            'views_today' => $views->whereDate('viewed_at', today())->count(),
            'views_this_week' => $views->whereBetween('viewed_at', [
                now()->startOfWeek(), 
                now()->endOfWeek()
            ])->count(),
            'views_this_month' => $views->whereMonth('viewed_at', now()->month)
                                      ->whereYear('viewed_at', now()->year)
                                      ->count(),
            'most_recent_view' => $views->latest('viewed_at')->first()?->viewed_at,
        ];
    }

    public function cleanupOldViews(int $daysToKeep = 365): int
    {
        return ModelView::where('viewed_at', '<', now()->subDays($daysToKeep))->delete();
    }

    /**
     * Check if a guest user has reached their view limit.
     */
    public function hasGuestReachedViewLimit(?int $userId): bool
    {
        if (!$userId) {
            return false; // No user ID means not a guest user scenario
        }

        $user = \App\Models\User::find($userId);
        if (!$user || !$user->isGuest()) {
            return false; // Not a guest user
        }

        return $user->hasReachedViewLimit();
    }

    /**
     * Get remaining views for a guest user.
     */
    public function getRemainingViewsForGuest(?int $userId): int
    {
        if (!$userId) {
            return 0;
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return 0;
        }

        return $user->getRemainingViews();
    }
}