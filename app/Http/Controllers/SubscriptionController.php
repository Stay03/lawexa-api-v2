<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\SubscriptionCollection;
use App\Http\Resources\SubscriptionInvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Validate query parameters
        $validated = $request->validate([
            'status' => 'sometimes|string|in:active,attention,completed,cancelled,non-renewing',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'sort_by' => 'sometimes|string|in:created_at,updated_at,next_payment_date,amount,status',
            'sort_direction' => 'sometimes|string|in:asc,desc',
        ]);
        
        $subscriptions = $this->subscriptionService->getUserSubscriptions($user, $validated);
        $subscriptionCollection = new SubscriptionCollection($subscriptions);
        
        return ApiResponse::success(
            $subscriptionCollection->toArray($request),
            'Subscriptions retrieved successfully'
        );
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
                
                // Send subscription confirmation email
                $this->notificationService->sendSubscriptionCreatedEmail($user, $subscription);
                
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
        
        if ($subscription->user_id !== $user->id) {
            return ApiResponse::error('Unauthorized. You can only view your own subscriptions.', 403);
        }
        
        $subscription->load(['plan', 'invoices']);
        
        return ApiResponse::success([
            'subscription' => new SubscriptionResource($subscription)
        ], 'Subscription retrieved successfully');
    }

    public function cancel(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if ($subscription->user_id !== $user->id) {
            return ApiResponse::error('Unauthorized. You can only cancel your own subscriptions.', 403);
        }

        try {
            $cancelledSubscription = $this->subscriptionService->cancelSubscription($subscription);
            
            // Send cancellation confirmation email
            $this->notificationService->sendSubscriptionCancelledEmail($user, $cancelledSubscription);
            
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
        
        if ($subscription->user_id !== $user->id) {
            return ApiResponse::error('Unauthorized. You can only reactivate your own subscriptions.', 403);
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
        
        if ($subscription->user_id !== $user->id) {
            return ApiResponse::error('Unauthorized. You can only view invoices for your own subscriptions.', 403);
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
        
        if ($subscription->user_id !== $user->id) {
            return ApiResponse::error('Unauthorized. You can only sync your own subscriptions.', 403);
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
