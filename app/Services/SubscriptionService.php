<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SubscriptionService
{
    public function __construct(
        private PaystackService $paystackService
    ) {}

    public function subscribeUserToPlan(User $user, Plan $plan, ?string $authorizationCode = null): Subscription
    {
        if ($user->hasActiveSubscription()) {
            throw new \Exception('User already has an active subscription. Please cancel current subscription first.');
        }

        return $this->paystackService->subscribeUserToPlan($user, $plan, $authorizationCode);
    }

    public function createSubscriptionWithPayment(User $user, Plan $plan, array $paymentData = []): array
    {
        if ($user->hasActiveSubscription()) {
            throw new \Exception('User already has an active subscription. Please cancel current subscription first.');
        }

        $transactionData = [
            'email' => $user->email,
            'amount' => $plan->amount,
            'plan' => $plan->plan_code,
            'callback_url' => $paymentData['callback_url'] ?? null,
            'metadata' => array_merge([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ], $paymentData['metadata'] ?? []),
        ];

        return $this->paystackService->initializeTransaction($transactionData);
    }

    public function getUserSubscriptions(User $user): Collection
    {
        return $user->subscriptions()
            ->with(['plan'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getSubscription(int $subscriptionId, ?int $userId = null): Subscription
    {
        $query = Subscription::with(['user', 'plan', 'invoices']);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->findOrFail($subscriptionId);
    }

    public function cancelSubscription(Subscription $subscription): Subscription
    {
        if (!$subscription->canBeCancelled()) {
            throw new \Exception('Subscription cannot be cancelled in its current state.');
        }

        $this->paystackService->disableSubscription(
            $subscription->subscription_code,
            $subscription->email_token
        );

        $subscription->update(['status' => 'cancelled']);
        
        return $subscription->refresh();
    }

    public function reactivateSubscription(Subscription $subscription): Subscription
    {
        if ($subscription->status !== 'cancelled') {
            throw new \Exception('Only cancelled subscriptions can be reactivated.');
        }

        $this->paystackService->enableSubscription(
            $subscription->subscription_code,
            $subscription->email_token
        );

        $subscription->update(['status' => 'active']);
        
        return $subscription->refresh();
    }

    public function generateManagementLink(Subscription $subscription): string
    {
        $response = $this->paystackService->generateSubscriptionManagementLink(
            $subscription->subscription_code
        );

        return $response['data']['link'];
    }

    public function sendManagementEmail(Subscription $subscription): void
    {
        $this->paystackService->sendSubscriptionManagementEmail(
            $subscription->subscription_code
        );
    }

    public function updateSubscriptionFromPaystack(Subscription $subscription): Subscription
    {
        $paystackResponse = $this->paystackService->fetchSubscription(
            $subscription->subscription_code
        );
        
        $paystackSubscription = $paystackResponse['data'];

        $subscription->update([
            'status' => $paystackSubscription['status'],
            'amount' => $paystackSubscription['amount'],
            'next_payment_date' => $paystackSubscription['next_payment_date'] ?? null,
            'cron_expression' => $paystackSubscription['cron_expression'] ?? null,
            'authorization_code' => $paystackSubscription['authorization']['authorization_code'] ?? null,
            'authorization_data' => $paystackSubscription['authorization'] ?? null,
            'metadata' => $paystackSubscription,
        ]);

        return $subscription->refresh();
    }

    public function switchUserPlan(User $user, Plan $newPlan, ?string $authorizationCode = null): Subscription
    {
        $currentSubscription = $user->activeSubscription;
        
        if ($currentSubscription) {
            $this->cancelSubscription($currentSubscription);
        }

        return $this->subscribeUserToPlan($user, $newPlan, $authorizationCode);
    }

    public function getSubscriptionInvoices(Subscription $subscription): Collection
    {
        return $subscription->invoices()
            ->orderBy('period_start', 'desc')
            ->get();
    }

    public function getActiveSubscriptions(): Collection
    {
        return Subscription::active()
            ->with(['user', 'plan'])
            ->get();
    }

    public function getExpiringSubscriptions(int $days = 7): Collection
    {
        return Subscription::active()
            ->whereDate('next_payment_date', '<=', now()->addDays($days))
            ->with(['user', 'plan'])
            ->get();
    }

    public function getFailedSubscriptions(): Collection
    {
        return Subscription::whereIn('status', ['attention', 'cancelled'])
            ->with(['user', 'plan'])
            ->get();
    }
}