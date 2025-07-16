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

    public function getUserSubscriptions(User $user, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $user->subscriptions()->with(['plan']);

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Apply pagination
        $perPage = $filters['per_page'] ?? 10;
        
        return $query->paginate($perPage);
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
        // if ($subscription->status !== 'cancelled' || $subscription->status !== 'non-renewing') {
        //     throw new \Exception('Only cancelled subscriptions can be reactivated.');
        // }

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
            ->with(['subscription.user'])
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

    public function getAllSubscriptions(array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Subscription::with(['user', 'plan']);

        // Apply search filter (user email or name)
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply plan filter
        if (!empty($filters['plan_id'])) {
            $query->where('plan_id', $filters['plan_id']);
        }

        // Apply date range filters
        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        if (!empty($filters['next_payment_from'])) {
            $query->whereDate('next_payment_date', '>=', $filters['next_payment_from']);
        }

        if (!empty($filters['next_payment_to'])) {
            $query->whereDate('next_payment_date', '<=', $filters['next_payment_to']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Apply pagination
        $perPage = $filters['per_page'] ?? 10;
        
        return $query->paginate($perPage);
    }
}