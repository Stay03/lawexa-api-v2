<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSubscriptionWebhookNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public string $eventType
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        try {
            $subscription = $this->subscription->load(['user', 'plan']);
            
            switch ($this->eventType) {
                case 'subscription.create':
                case 'subscription.enable':
                    $notificationService->sendSubscriptionCreatedEmail(
                        $subscription->user, 
                        $subscription
                    );
                    break;
                    
                case 'subscription.disable':
                case 'subscription.not_renew':
                    $notificationService->sendSubscriptionCancelledEmail(
                        $subscription->user, 
                        $subscription
                    );
                    break;
            }
            
            Log::info('Subscription webhook notification sent', [
                'subscription_id' => $subscription->id,
                'event_type' => $this->eventType,
                'user_id' => $subscription->user->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send subscription webhook notification', [
                'subscription_id' => $this->subscription->id,
                'event_type' => $this->eventType,
                'error' => $e->getMessage()
            ]);
            
            throw $e; // Re-throw to trigger job retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Subscription webhook notification job failed', [
            'subscription_id' => $this->subscription->id,
            'event_type' => $this->eventType,
            'error' => $exception->getMessage()
        ]);
    }
}
