<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ModelView extends Model
{
    protected $fillable = [
        'viewable_type',
        'viewable_id',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent_hash',
        'user_agent',
        'ip_country',
        'ip_country_code',
        'ip_continent',
        'ip_continent_code',
        'ip_region',
        'ip_city',
        'ip_timezone',
        'device_type',
        'device_platform',
        'device_browser',
        'viewed_at',
        'is_bot',
        'bot_name',
        'is_search_engine',
        'is_social_media',
        'search_query',
        'is_from_search',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'is_bot' => 'boolean',
        'is_search_engine' => 'boolean',
        'is_social_media' => 'boolean',
        'is_from_search' => 'boolean',
    ];

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForGuest(Builder $query, string $sessionId, string $ipAddress, string $userAgentHash): Builder
    {
        return $query->where('session_id', $sessionId)
                    ->where('ip_address', $ipAddress)
                    ->where('user_agent_hash', $userAgentHash);
    }

    public function scopeForViewable(Builder $query, string $viewableType, int $viewableId): Builder
    {
        return $query->where('viewable_type', $viewableType)
                    ->where('viewable_id', $viewableId);
    }

    public function scopeWithinCooldown(Builder $query, int $cooldownValue, string $cooldownUnit = 'hours'): Builder
    {
        $carbonMethod = match($cooldownUnit) {
            'seconds' => 'subSeconds',
            'minutes' => 'subMinutes',
            'hours' => 'subHours',
            'days' => 'subDays',
            default => 'subHours'
        };
        
        return $query->where('viewed_at', '>=', Carbon::now()->$carbonMethod($cooldownValue));
    }

    public static function canView(
        string $viewableType,
        int $viewableId,
        ?int $userId = null,
        ?string $sessionId = null,
        ?string $ipAddress = null,
        ?string $userAgentHash = null
    ): bool {
        $cooldownValue = $userId 
            ? config('view_tracking.cooldown.authenticated', 1)
            : config('view_tracking.cooldown.guest', 2);
        $cooldownUnit = config('view_tracking.cooldown.unit', 'hours');
        
        $query = static::forViewable($viewableType, $viewableId)
                      ->withinCooldown($cooldownValue, $cooldownUnit);

        // Check cooldown period first
        if ($userId) {
            $cooldownPassed = !$query->forUser($userId)->exists();
        } else {
            $cooldownPassed = !$query->forGuest($sessionId, $ipAddress, $userAgentHash)->exists();
        }
        
        if (!$cooldownPassed) {
            return false;
        }
        
        // For guest users, also check total view limit
        if ($userId) {
            $user = User::find($userId);
            if ($user && $user->isGuest()) {
                return static::canGuestView($userId);
            }
        }
        
        return true;
    }

    /**
     * Check if a bot can view a specific model (skip cooldown, but check guest limits)
     */
    public static function canViewBot(
        string $viewableType,
        int $viewableId,
        ?int $userId = null
    ): bool {
        // For bots, we skip cooldown checks but still enforce guest limits
        if ($userId) {
            $user = User::find($userId);
            if ($user && $user->isGuest()) {
                // Bot guest users are typically created with extended expiration
                // and may have different limits, but we still check
                return static::canGuestView($userId);
            }
        }
        
        // For non-guest users or no user ID, allow the view
        return true;
    }

    /**
     * Scope to filter bot views only
     */
    public function scopeBotViews(Builder $query): Builder
    {
        return $query->where('is_bot', true);
    }

    /**
     * Scope to filter human views only
     */
    public function scopeHumanViews(Builder $query): Builder
    {
        return $query->where('is_bot', false)->orWhereNull('is_bot');
    }

    /**
     * Scope to filter search engine bot views
     */
    public function scopeSearchEngineViews(Builder $query): Builder
    {
        return $query->where('is_search_engine', true);
    }

    /**
     * Scope to filter social media bot views
     */
    public function scopeSocialMediaViews(Builder $query): Builder
    {
        return $query->where('is_social_media', true);
    }

    /**
     * Scope to filter by specific bot name
     */
    public function scopeByBotName(Builder $query, string $botName): Builder
    {
        return $query->where('bot_name', $botName);
    }

    /**
     * Scope to get the most popular bot names
     */
    public function scopeMostPopularBots(Builder $query): Builder
    {
        return $query->botViews()
                    ->selectRaw('bot_name, COUNT(*) as views_count')
                    ->whereNotNull('bot_name')
                    ->groupBy('bot_name')
                    ->orderByDesc('views_count');
    }

    /**
     * Scope to get views within a date range
     */
    public function scopeWithinDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('viewed_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter views that came from search
     */
    public function scopeFromSearch(Builder $query): Builder
    {
        return $query->where('is_from_search', true);
    }

    /**
     * Scope to filter views that didn't come from search
     */
    public function scopeNotFromSearch(Builder $query): Builder
    {
        return $query->where('is_from_search', false);
    }

    /**
     * Scope to filter views by specific search query (exact match)
     */
    public function scopeBySearchQuery(Builder $query, string $searchQuery): Builder
    {
        return $query->where('search_query', $searchQuery);
    }

    /**
     * Scope to filter views by similar search queries (LIKE match)
     */
    public function scopeSimilarSearchQueries(Builder $query, string $searchQuery): Builder
    {
        return $query->where('search_query', 'LIKE', '%' . $searchQuery . '%');
    }

    public static function recordView(
        string $viewableType,
        int $viewableId,
        ?int $userId = null,
        ?string $sessionId = null,
        ?string $ipAddress = null,
        ?string $userAgentHash = null,
        ?string $userAgent = null,
        ?string $ipCountry = null,
        ?string $ipCountryCode = null,
        ?string $ipContinent = null,
        ?string $ipContinentCode = null,
        ?string $ipRegion = null,
        ?string $ipCity = null,
        ?string $ipTimezone = null,
        ?string $deviceType = null,
        ?string $devicePlatform = null,
        ?string $deviceBrowser = null,
        ?bool $isBot = null,
        ?string $botName = null,
        ?bool $isSearchEngine = null,
        ?bool $isSocialMedia = null,
        ?string $searchQuery = null,
        ?bool $isFromSearch = null
    ): ?static {
        try {
            // Truncate search query to 500 characters if provided
            if ($searchQuery !== null && strlen($searchQuery) > 500) {
                $searchQuery = substr($searchQuery, 0, 500);
            }

            // Auto-detect is_from_search if not explicitly provided
            if ($isFromSearch === null) {
                $isFromSearch = !empty($searchQuery);
            }

            return static::create([
                'viewable_type' => $viewableType,
                'viewable_id' => $viewableId,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'ip_address' => $ipAddress,
                'user_agent_hash' => $userAgentHash,
                'user_agent' => $userAgent,
                'ip_country' => $ipCountry,
                'ip_country_code' => $ipCountryCode,
                'ip_continent' => $ipContinent,
                'ip_continent_code' => $ipContinentCode,
                'ip_region' => $ipRegion,
                'ip_city' => $ipCity,
                'ip_timezone' => $ipTimezone,
                'device_type' => $deviceType,
                'device_platform' => $devicePlatform,
                'device_browser' => $deviceBrowser,
                'viewed_at' => Carbon::now(),
                'is_bot' => $isBot,
                'bot_name' => $botName,
                'is_search_engine' => $isSearchEngine,
                'is_social_media' => $isSocialMedia,
                'search_query' => $searchQuery,
                'is_from_search' => $isFromSearch,
            ]);
        } catch (\Exception $e) {
            // Handle duplicate key constraint violations gracefully
            return null;
        }
    }

    /**
     * Check if a guest user can view more content based on their total view limit.
     */
    public static function canGuestView(int $userId): bool
    {
        $limit = config('view_tracking.guest_limits.total_views', 20);
        $totalViews = static::getTotalViewsForUser($userId);
        
        return $totalViews < $limit;
    }

    /**
     * Get total view count for a user across all models.
     */
    public static function getTotalViewsForUser(int $userId): int
    {
        return static::where('user_id', $userId)->count();
    }

    /**
     * Get remaining views for a guest user.
     */
    public static function getRemainingViewsForGuest(int $userId): int
    {
        $limit = config('view_tracking.guest_limits.total_views', 20);
        $totalViews = static::getTotalViewsForUser($userId);
        
        return max(0, $limit - $totalViews);
    }
}
