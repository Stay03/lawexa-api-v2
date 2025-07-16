<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key') ?? '';
        $this->baseUrl = 'https://api.paystack.co';
        
        if (empty($this->secretKey)) {
            throw new \Exception('Paystack secret key is not configured. Please set PAYSTACK_SECRET_KEY in your environment file.');
        }
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $httpClient = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ]);

        // Skip SSL verification in local development
        if (app()->environment('local')) {
            $httpClient = $httpClient->withOptions([
                'verify' => false,
            ]);
        }

        $response = $httpClient->{$method}($this->baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            Log::error('Paystack API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            throw new \Exception('Paystack API Error: ' . $response->body());
        }

        return $response->json();
    }

    public function createPlan(array $planData): array
    {
        $data = [
            'name' => $planData['name'],
            'amount' => $planData['amount'],
            'interval' => $planData['interval'],
        ];

        if (isset($planData['description'])) {
            $data['description'] = $planData['description'];
        }

        if (isset($planData['invoice_limit']) && $planData['invoice_limit'] > 0) {
            $data['invoice_limit'] = $planData['invoice_limit'];
        }

        if (isset($planData['send_invoices'])) {
            $data['send_invoices'] = $planData['send_invoices'];
        }

        if (isset($planData['send_sms'])) {
            $data['send_sms'] = $planData['send_sms'];
        }

        return $this->makeRequest('post', '/plan', $data);
    }

    public function updatePlan(string $planCode, array $planData): array
    {
        return $this->makeRequest('put', "/plan/{$planCode}", $planData);
    }

    public function fetchPlan(string $planCode): array
    {
        return $this->makeRequest('get', "/plan/{$planCode}");
    }

    public function listPlans(): array
    {
        return $this->makeRequest('get', '/plan');
    }

    public function createSubscription(User $user, Plan $plan, ?string $authorizationCode = null): array
    {
        if (!$user->customer_code) {
            throw new \Exception('User must have a customer code to create subscription');
        }

        $data = [
            'customer' => $user->customer_code,
            'plan' => $plan->plan_code,
        ];

        if ($authorizationCode) {
            $data['authorization'] = $authorizationCode;
        }

        return $this->makeRequest('post', '/subscription', $data);
    }

    public function fetchSubscription(string $subscriptionCode): array
    {
        return $this->makeRequest('get', "/subscription/{$subscriptionCode}");
    }

    public function disableSubscription(string $subscriptionCode, string $token): array
    {
        return $this->makeRequest('post', "/subscription/disable", [
            'code' => $subscriptionCode,
            'token' => $token,
        ]);
    }

    public function enableSubscription(string $subscriptionCode, string $token): array
    {
        return $this->makeRequest('post', "/subscription/enable", [
            'code' => $subscriptionCode,
            'token' => $token,
        ]);
    }

    public function generateSubscriptionManagementLink(string $subscriptionCode): array
    {
        return $this->makeRequest('get', "/subscription/{$subscriptionCode}/manage/link");
    }

    public function sendSubscriptionManagementEmail(string $subscriptionCode): array
    {
        return $this->makeRequest('post', "/subscription/{$subscriptionCode}/manage/email");
    }

    public function initializeTransaction(array $transactionData): array
    {
        return $this->makeRequest('post', '/transaction/initialize', $transactionData);
    }

    public function verifyTransaction(string $reference): array
    {
        return $this->makeRequest('get', "/transaction/verify/{$reference}");
    }

    public function createCustomer(User $user): array
    {
        $data = [
            'email' => $user->email,
            'first_name' => explode(' ', $user->name)[0] ?? '',
            'last_name' => explode(' ', $user->name, 2)[1] ?? '',
        ];

        return $this->makeRequest('post', '/customer', $data);
    }

    public function fetchCustomer(string $customerCode): array
    {
        return $this->makeRequest('get', "/customer/{$customerCode}");
    }

    public function subscribeUserToPlan(User $user, Plan $plan, ?string $authorizationCode = null): Subscription
    {
        if (!$user->customer_code) {
            $customerResponse = $this->createCustomer($user);
            $user->update(['customer_code' => $customerResponse['data']['customer_code']]);
        }

        $response = $this->createSubscription($user, $plan, $authorizationCode);
        $subscriptionData = $response['data'];

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'subscription_code' => $subscriptionData['subscription_code'],
            'email_token' => $subscriptionData['email_token'],
            'status' => $subscriptionData['status'],
            'quantity' => $subscriptionData['quantity'] ?? 1,
            'amount' => $subscriptionData['amount'],
            'currency' => $plan->currency,
            'start_date' => now(),
            'next_payment_date' => $subscriptionData['next_payment_date'] ?? null,
            'cron_expression' => $subscriptionData['cron_expression'] ?? null,
            'authorization_code' => $subscriptionData['authorization']['authorization_code'] ?? null,
            'authorization_data' => $subscriptionData['authorization'] ?? null,
            'invoice_limit' => $subscriptionData['invoice_limit'] ?? 0,
            'metadata' => $subscriptionData,
        ]);
    }

    public function handleWebhookEvent(array $eventData): void
    {
        $event = $eventData['event'];
        $data = $eventData['data'];

        Log::info('Paystack Webhook Event', ['event' => $event, 'data' => $data]);

        switch ($event) {
            case 'subscription.create':
                $this->handleSubscriptionCreated($data);
                break;
            case 'subscription.disable':
                $this->handleSubscriptionDisabled($data);
                break;
            case 'subscription.not_renew':
                $this->handleSubscriptionNotRenewing($data);
                break;
            case 'invoice.create':
                $this->handleInvoiceCreated($data);
                break;
            case 'invoice.update':
                $this->handleInvoiceUpdated($data);
                break;
            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($data);
                break;
            case 'charge.success':
                $this->handleChargeSuccess($data);
                break;
            default:
                Log::info('Unhandled Paystack webhook event', ['event' => $event]);
        }
    }

    private function handleSubscriptionCreated(array $data): void
    {
        // Update user's customer code if not set
        $customerCode = $data['customer']['customer_code'];
        $customerEmail = $data['customer']['email'];
        
        $user = User::whereRaw('LOWER(email) = LOWER(?)', [$customerEmail])->first();
        if ($user && !$user->customer_code) {
            $user->update(['customer_code' => $customerCode]);
        }
        
        if (!$user) {
            Log::warning('User not found for subscription webhook', ['email' => $customerEmail]);
            return;
        }

        // Find the plan by plan_code
        $plan = Plan::where('plan_code', $data['plan']['plan_code'])->first();
        if (!$plan) {
            Log::warning('Plan not found for subscription webhook', ['plan_code' => $data['plan']['plan_code']]);
            return;
        }

        // Create or update subscription
        $subscription = Subscription::updateOrCreate(
            ['subscription_code' => $data['subscription_code']],
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'email_token' => $data['email_token'] ?? null,
                'status' => $data['status'],
                'quantity' => $data['quantity'] ?? 1,
                'amount' => $data['amount'],
                'currency' => $plan->currency,
                'start_date' => $data['createdAt'] ?? now(),
                'next_payment_date' => $data['next_payment_date'] ?? null,
                'cron_expression' => $data['cron_expression'] ?? null,
                'authorization_code' => $data['authorization']['authorization_code'] ?? null,
                'authorization_data' => $data['authorization'] ?? null,
                'invoice_limit' => $data['invoice_limit'] ?? 0,
                'metadata' => $data,
            ]
        );

        // Create initial invoice if we have recent charge data
        $this->createInitialInvoiceForSubscription($subscription, $data);

        Log::info('Subscription created/updated', [
            'user_id' => $user->id,
            'subscription_code' => $data['subscription_code'],
            'plan_code' => $data['plan']['plan_code']
        ]);
    }

    private function handleSubscriptionDisabled(array $data): void
    {
        $subscription = Subscription::where('subscription_code', $data['subscription_code'])->first();
        if ($subscription) {
            $subscription->update(['status' => $data['status']]);
        }
    }

    private function handleSubscriptionNotRenewing(array $data): void
    {
        $subscription = Subscription::where('subscription_code', $data['subscription_code'])->first();
        if ($subscription) {
            $subscription->update(['status' => 'non-renewing']);
        }
    }

    private function handleInvoiceCreated(array $data): void
    {
        $subscription = Subscription::where('subscription_code', $data['subscription']['subscription_code'])->first();
        if ($subscription) {
            SubscriptionInvoice::updateOrCreate(
                ['invoice_code' => $data['invoice_code']],
                [
                    'subscription_id' => $subscription->id,
                    'amount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'NGN',
                    'status' => $data['status'],
                    'paid' => $data['paid'] ?? false,
                    'paid_at' => $data['paid_at'] ?? null,
                    'period_start' => $data['period_start'],
                    'period_end' => $data['period_end'],
                    'description' => $data['description'] ?? null,
                    'transaction_reference' => $data['transaction']['reference'] ?? null,
                    'authorization_data' => $data['authorization'] ?? null,
                    'metadata' => $data,
                ]
            );
        }
    }

    private function handleInvoiceUpdated(array $data): void
    {
        $invoice = SubscriptionInvoice::where('invoice_code', $data['invoice_code'])->first();
        if ($invoice) {
            $invoice->update([
                'status' => $data['status'],
                'paid' => $data['paid'] ?? false,
                'paid_at' => $data['paid_at'] ?? null,
                'transaction_reference' => $data['transaction']['reference'] ?? null,
                'metadata' => $data,
            ]);
        }
    }

    private function handleInvoicePaymentFailed(array $data): void
    {
        $subscription = Subscription::where('subscription_code', $data['subscription']['subscription_code'])->first();
        if ($subscription) {
            $subscription->update(['status' => 'attention']);
            
            SubscriptionInvoice::updateOrCreate(
                ['invoice_code' => $data['invoice_code']],
                [
                    'subscription_id' => $subscription->id,
                    'amount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'NGN',
                    'status' => 'failed',
                    'paid' => false,
                    'period_start' => $data['period_start'],
                    'period_end' => $data['period_end'],
                    'description' => $data['description'] ?? null,
                    'authorization_data' => $data['authorization'] ?? null,
                    'metadata' => $data,
                ]
            );
        }
    }

    private function handleChargeSuccess(array $data): void
    {
        // Update user's customer code if not set
        if (isset($data['customer']['customer_code']) && isset($data['customer']['email'])) {
            $user = User::whereRaw('LOWER(email) = LOWER(?)', [$data['customer']['email']])->first();
            if ($user && !$user->customer_code) {
                $user->update(['customer_code' => $data['customer']['customer_code']]);
                Log::info('Updated user customer code', [
                    'user_id' => $user->id,
                    'customer_code' => $data['customer']['customer_code']
                ]);
            }
        }

        // Handle subscription-related charges
        if (isset($data['plan']) && !empty($data['plan'])) {
            $subscription = Subscription::where('authorization_code', $data['authorization']['authorization_code'])
                ->first();
                
            if ($subscription) {
                // Calculate next payment date based on plan interval
                $interval = $data['plan']['interval'] ?? 'monthly';
                $nextPaymentDate = $this->calculateNextPaymentDate(now(), $interval);
                
                $subscription->update([
                    'status' => 'active',
                    'next_payment_date' => $nextPaymentDate
                ]);
                
                Log::info('Updated subscription status and next payment date', [
                    'subscription_id' => $subscription->id,
                    'reference' => $data['reference'],
                    'next_payment_date' => $nextPaymentDate->toDateTimeString(),
                    'interval' => $interval
                ]);
            }
        }

        // Create invoice record for the successful charge
        if (isset($data['plan']) && !empty($data['plan'])) {
            $subscription = Subscription::where('authorization_code', $data['authorization']['authorization_code'])->first();
            
            if ($subscription) {
                // Generate a unique invoice code and calculate billing period
                $invoiceCode = 'INV_' . strtoupper(uniqid()) . '_' . $subscription->id;
                $interval = $data['plan']['interval'] ?? 'monthly';
                $periodStart = now();
                $periodEnd = $this->calculatePeriodEnd($periodStart, $interval);
                
                SubscriptionInvoice::updateOrCreate(
                    ['transaction_reference' => $data['reference']],
                    [
                        'subscription_id' => $subscription->id,
                        'invoice_code' => $invoiceCode,
                        'amount' => $data['amount'],
                        'currency' => $data['currency'] ?? 'NGN',
                        'status' => 'paid',
                        'paid' => true,
                        'paid_at' => $data['paid_at'] ?? $data['paidAt'] ?? now(),
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'description' => 'Subscription payment',
                        'authorization_data' => $data['authorization'] ?? null,
                        'metadata' => $data,
                    ]
                );

                Log::info('Created invoice for charge success', [
                    'subscription_id' => $subscription->id,
                    'reference' => $data['reference']
                ]);
            } else {
                // If subscription doesn't exist yet, store charge data for later processing
                Log::info('Charge success received before subscription creation', [
                    'reference' => $data['reference'],
                    'authorization_code' => $data['authorization']['authorization_code'],
                    'plan_code' => $data['plan']['plan_code']
                ]);
            }
        }
    }

    private function createInitialInvoiceForSubscription(Subscription $subscription, array $subscriptionData): void
    {
        // Generate invoice code and calculate billing period
        $invoiceCode = 'INV_' . strtoupper(uniqid()) . '_' . $subscription->id;
        $interval = $subscriptionData['plan']['interval'] ?? 'monthly';
        $periodStart = now();
        $periodEnd = $this->calculatePeriodEnd($periodStart, $interval);
        
        SubscriptionInvoice::updateOrCreate(
            [
                'subscription_id' => $subscription->id,
                'description' => 'Initial subscription payment'
            ],
            [
                'invoice_code' => $invoiceCode,
                'amount' => $subscription->amount,
                'currency' => $subscription->currency,
                'status' => 'paid',
                'paid' => true,
                'paid_at' => $subscription->start_date,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'authorization_data' => $subscriptionData['authorization'] ?? null,
                'metadata' => $subscriptionData,
            ]
        );

        Log::info('Created initial invoice for subscription', [
            'subscription_id' => $subscription->id,
            'amount' => $subscription->amount,
            'interval' => $interval
        ]);
    }

    private function calculatePeriodEnd(\Carbon\Carbon $periodStart, string $interval): \Carbon\Carbon
    {
        return match($interval) {
            'hourly' => $periodStart->copy()->addHour(),
            'daily' => $periodStart->copy()->addDay(),
            'weekly' => $periodStart->copy()->addWeek(),
            'monthly' => $periodStart->copy()->addMonth(),
            'quarterly' => $periodStart->copy()->addQuarter(),
            'biannually' => $periodStart->copy()->addMonths(6),
            'annually' => $periodStart->copy()->addYear(),
            default => $periodStart->copy()->addMonth(), // fallback to monthly
        };
    }

    private function calculateNextPaymentDate(\Carbon\Carbon $currentDate, string $interval): \Carbon\Carbon
    {
        return match($interval) {
            'hourly' => $currentDate->copy()->addHour(),
            'daily' => $currentDate->copy()->addDay(),
            'weekly' => $currentDate->copy()->addWeek(),
            'monthly' => $currentDate->copy()->addMonth(),
            'quarterly' => $currentDate->copy()->addQuarter(),
            'biannually' => $currentDate->copy()->addMonths(6),
            'annually' => $currentDate->copy()->addYear(),
            default => $currentDate->copy()->addMonth(), // fallback to monthly
        };
    }
}