<?php

namespace App\Traits;

use App\Models\ModelView;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasViewTracking
{
    public function views(): MorphMany
    {
        return $this->morphMany(ModelView::class, 'viewable');
    }

    public function viewsCount(): int
    {
        // Use eager loaded count if available, otherwise query
        if (isset($this->views_count)) {
            return (int) $this->views_count;
        }
        
        return $this->views()->count();
    }
    
    public function scopeWithViewsCount($query)
    {
        return $query->withCount('views as views_count');
    }

    public function getViewsToday(): int
    {
        return $this->views()
                   ->whereDate('viewed_at', today())
                   ->count();
    }

    public function getViewsThisWeek(): int
    {
        return $this->views()
                   ->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()])
                   ->count();
    }

    public function getViewsThisMonth(): int
    {
        return $this->views()
                   ->whereMonth('viewed_at', now()->month)
                   ->whereYear('viewed_at', now()->year)
                   ->count();
    }

    public function getUniqueViewersCount(): int
    {
        return $this->views()
                   ->distinct('user_id')
                   ->whereNotNull('user_id')
                   ->count();
    }

    public function getMostRecentView(): ?ModelView
    {
        return $this->views()
                   ->latest('viewed_at')
                   ->first();
    }

    public function hasBeenViewedBy(?int $userId = null, ?string $sessionId = null, ?string $ipAddress = null, ?string $userAgentHash = null): bool
    {
        $query = $this->views();

        if ($userId) {
            return $query->where('user_id', $userId)->exists();
        } else {
            return $query->where('session_id', $sessionId)
                        ->where('ip_address', $ipAddress)
                        ->where('user_agent_hash', $userAgentHash)
                        ->exists();
        }
    }
}