<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\SubscriptionInvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminSubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    public function show(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'researcher', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins, researchers, and superadmins can view subscription details.', 403);
        }
        
        $subscription->load(['plan', 'user', 'invoices']);
        
        return ApiResponse::success([
            'subscription' => new SubscriptionResource($subscription)
        ], 'Subscription details retrieved successfully');
    }

    public function cancel(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins and superadmins can cancel subscriptions.', 403);
        }

        try {
            $cancelledSubscription = $this->subscriptionService->cancelSubscription($subscription);
            
            return ApiResponse::success([
                'subscription' => new SubscriptionResource($cancelledSubscription->load(['plan', 'user']))
            ], 'Subscription cancelled successfully by admin');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to cancel subscription: ' . $e->getMessage(), 500);
        }
    }

    public function reactivate(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins and superadmins can reactivate subscriptions.', 403);
        }

        try {
            $reactivatedSubscription = $this->subscriptionService->reactivateSubscription($subscription);
            
            return ApiResponse::success([
                'subscription' => new SubscriptionResource($reactivatedSubscription->load(['plan', 'user']))
            ], 'Subscription reactivated successfully by admin');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to reactivate subscription: ' . $e->getMessage(), 500);
        }
    }

    public function invoices(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'researcher', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins, researchers, and superadmins can view subscription invoices.', 403);
        }
        
        $invoices = $this->subscriptionService->getSubscriptionInvoices($subscription);
        
        return ApiResponse::success([
            'invoices' => SubscriptionInvoiceResource::collection($invoices),
            'subscription' => [
                'id' => $subscription->id,
                'user' => [
                    'id' => $subscription->user->id,
                    'name' => $subscription->user->name,
                    'email' => $subscription->user->email
                ]
            ]
        ], 'Subscription invoices retrieved successfully');
    }

    public function sync(Request $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins and superadmins can sync subscriptions.', 403);
        }

        try {
            $syncedSubscription = $this->subscriptionService->updateSubscriptionFromPaystack($subscription);
            
            return ApiResponse::success([
                'subscription' => new SubscriptionResource($syncedSubscription->load(['plan', 'user']))
            ], 'Subscription synced with Paystack successfully by admin');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to sync subscription: ' . $e->getMessage(), 500);
        }
    }
}