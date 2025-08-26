<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\ModelView;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\SecurityLoggerService;

class CleanupExpiredGuests implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private ?SecurityLoggerService $securityLogger = null
    ) {
        $this->securityLogger = $this->securityLogger ?? app(SecurityLoggerService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting guest cleanup job');

        $deletedCount = 0;
        
        // Find guest users that should be cleaned up (expired OR inactive for 30+ days)
        $expiredGuests = User::where('role', 'guest')
            ->where(function ($query) {
                $query->where('guest_expires_at', '<', now())
                      ->orWhere('last_activity_at', '<', now()->subDays(30))
                      ->orWhereNull('last_activity_at'); // Guests with no activity at all
            })
            ->get();

        foreach ($expiredGuests as $guest) {
            DB::transaction(function () use ($guest, &$deletedCount) {
                // Determine deletion reason
                $reason = 'unknown';
                if ($guest->guest_expires_at && $guest->guest_expires_at < now()) {
                    $reason = 'expired';
                } elseif ($guest->last_activity_at && $guest->last_activity_at < now()->subDays(30)) {
                    $reason = 'inactive';
                } elseif (!$guest->last_activity_at) {
                    $reason = 'no_activity';
                }

                // Log individual guest deletion
                $this->securityLogger->logGuestSessionDeleted(
                    $guest->id,
                    $reason,
                    $guest->created_at?->toISOString()
                );
                
                // Revoke all tokens for this guest
                $guest->tokens()->delete();
                
                // Delete the guest user
                // Note: ModelView records are automatically deleted via cascade foreign key constraint
                $guest->delete();
                
                $deletedCount++;
            });
        }

        Log::info("Guest cleanup completed", [
            'deleted_guests' => $deletedCount,
            'total_checked' => $expiredGuests->count()
        ]);
    }
}
