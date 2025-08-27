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
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
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
        ?string $deviceBrowser = null
    ): ?static {
        try {
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
