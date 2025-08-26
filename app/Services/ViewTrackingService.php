<?php

namespace App\Services;

use App\Models\ModelView;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ViewTrackingService
{
    public function trackView(Model $model, Request $request): void
    {
        $viewData = $this->extractViewData($model, $request);
        
        // Use database transaction for atomic view tracking
        DB::transaction(function () use ($viewData) {
            // Double-check cooldown within transaction to prevent race conditions
            if ($this->canTrackView($viewData)) {
                $this->recordView($viewData);
            }
        });
    }

    public function canTrackView(array $viewData): bool
    {
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
            $viewData['user_agent_hash']
        );
    }

    private function extractViewData(Model $model, Request $request): array
    {
        $user = $request->user();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent() ?? '';
        
        // With guest authentication system, we always have a user (either real user or guest)
        // No need for session-based tracking anymore
        
        return [
            'viewable_type' => get_class($model),
            'viewable_id' => $model->getKey(),
            'user_id' => $user?->id,
            'session_id' => null, // No longer needed with guest user system
            'ip_address' => $ipAddress,
            'user_agent_hash' => hash('sha256', $userAgent . $ipAddress), // Hash for privacy
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