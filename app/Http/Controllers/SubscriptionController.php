<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\SubscriptionInvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscriptions = $this->subscriptionService->getUserSubscriptions($user);
        
        return ApiResponse::success([
            'subscriptions' => SubscriptionResource::collection($subscriptions)
        ], 'Subscriptions retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'authorization_code' => 'nullable|string',
            'callback_url' => 'nullable|url',
            'metadata' => 'nullable|array',
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($validated['plan_id']);

        try {
            if (isset($validated['authorization_code'])) {
                $subscription = $this->subscriptionService->subscribeUserToPlan(
                    $user, 
                    $plan, 
                    $validated['authorization_code']
                );
                
                return ApiResponse::success([
                    'subscription' => new SubscriptionResource($subscription->load('plan'))
                ], 'Subscription created successfully', 201);
            } else {
                $paymentData = [
                    'callback_url' => $validated['callback_url'] ?? null,
                    'metadata' => $validated['metadata'] ?? [],
                ];
                
                $transactionResponse = $this->subscriptionService->createSubscriptionWithPayment(
                    $user, 
                    $plan, 
                    $paymentData
                );
                
                return ApiResponse::success([
                    'payment_url' => $transactionResponse['data']['authorization_url'],
                    'access_code' => $transactionResponse['data']['access_code'],
                    'reference' => $transactionResponse['data']['reference'],
                ], 'Payment initialized. Complete payment to activate subscription', 201);
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create subscription: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if ($subscription->user_id !== $user->id && !$user->hasAdminAccess()) {
            return ApiResponse::error('Unauthorized', 403);
        }
        
        $subscription->load(['plan', 'invoices']);
        
        return ApiResponse::success([
            'subscription' => new SubscriptionResource($subscription)
        ], 'Subscription retrieved successfully');
    }

    public function cancel(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if ($subscription->user_id !== $user->id && !$user->hasAdminAccess()) {
            return ApiResponse::error('Unauthorized', 403);
        }

        try {
            $cancelledSubscription = $this->subscriptionService->cancelSubscription($subscription);
            
            return ApiResponse::success([
                'subscription' => new SubscriptionResource($cancelledSubscription->load('plan'))
            ], 'Subscription cancelled successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to cancel subscription: ' . $e->getMessage(), 500);
        }
    }

    public function reactivate(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if ($subscription->user_id !== $user->id && !$user->hasAdminAccess()) {
            return ApiResponse::error('Unauthorized', 403);
        }

        try {
            $reactivatedSubscription = $this->subscriptionService->reactivateSubscription($subscription);
            
            return ApiResponse::success([
                'subscription' => new SubscriptionResource($reactivatedSubscription->load('plan'))
            ], 'Subscription reactivated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to reactivate subscription: ' . $e->getMessage(), 500);
        }
    }

    public function invoices(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if ($subscription->user_id !== $user->id && !$user->hasAdminAccess()) {
            return ApiResponse::error('Unauthorized', 403);
        }
        
        $invoices = $this->subscriptionService->getSubscriptionInvoices($subscription);
        
        return ApiResponse::success([
            'invoices' => SubscriptionInvoiceResource::collection($invoices)
        ], 'Subscription invoices retrieved successfully');
    }

    public function managementLink(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if ($subscription->user_id !== $user->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        try {
            $managementLink = $this->subscriptionService->generateManagementLink($subscription);
            
            return ApiResponse::success([
                'management_link' => $managementLink
            ], 'Management link generated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to generate management link: ' . $e->getMessage(), 500);
        }
    }

    public function sendManagementEmail(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if ($subscription->user_id !== $user->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        try {
            $this->subscriptionService->sendManagementEmail($subscription);
            
            return ApiResponse::success([], 'Management email sent successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to send management email: ' . $e->getMessage(), 500);
        }
    }

    public function switchPlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'authorization_code' => 'nullable|string',
        ]);

        $user = $request->user();
        $newPlan = Plan::findOrFail($validated['plan_id']);

        try {
            $subscription = $this->subscriptionService->switchUserPlan(
                $user, 
                $newPlan, 
                $validated['authorization_code'] ?? null
            );
            
            return ApiResponse::success([
                'subscription' => new SubscriptionResource($subscription->load('plan'))
            ], 'Plan switched successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to switch plan: ' . $e->getMessage(), 500);
        }
    }

    public function sync(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if ($subscription->user_id !== $user->id && !$user->hasAdminAccess()) {
            return ApiResponse::error('Unauthorized', 403);
        }

        try {
            $syncedSubscription = $this->subscriptionService->updateSubscriptionFromPaystack($subscription);
            
            return ApiResponse::success([
                'subscription' => new SubscriptionResource($syncedSubscription->load('plan'))
            ], 'Subscription synced with Paystack successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to sync subscription: ' . $e->getMessage(), 500);
        }
    }
}
