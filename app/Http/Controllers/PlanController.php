<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlanResource;
use App\Http\Responses\ApiResponse;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PlanController extends Controller
{
    public function __construct(
        private PlanService $planService
    ) {}

    public function index(): JsonResponse
    {
        $plans = $this->planService->getPlansWithStats();
        
        return ApiResponse::success([
            'plans' => PlanResource::collection($plans)
        ], 'Plans retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins and superadmins can create plans.', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|integer|min:100',
            'interval' => 'required|in:hourly,daily,weekly,monthly,quarterly,biannually,annually',
            'invoice_limit' => 'nullable|integer|min:0',
            'send_invoices' => 'boolean',
            'send_sms' => 'boolean',
        ]);

        try {
            $plan = $this->planService->createPlan($validated);
            
            return ApiResponse::success([
                'plan' => new PlanResource($plan)
            ], 'Plan created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create plan: ' . $e->getMessage(), 500);
        }
    }

    public function show(Plan $plan): JsonResponse
    {
        $plan->loadCount(['subscriptions', 'activeSubscriptions']);
        
        return ApiResponse::success([
            'plan' => new PlanResource($plan)
        ], 'Plan retrieved successfully');
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins and superadmins can update plans.', 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'sometimes|integer|min:100',
            'interval' => 'sometimes|in:hourly,daily,weekly,monthly,quarterly,biannually,annually',
            'invoice_limit' => 'nullable|integer|min:0',
            'send_invoices' => 'boolean',
            'send_sms' => 'boolean',
            'update_existing_subscriptions' => 'boolean',
        ]);

        try {
            $updatedPlan = $this->planService->updatePlan($plan, $validated);
            
            return ApiResponse::success([
                'plan' => new PlanResource($updatedPlan)
            ], 'Plan updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update plan: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, Plan $plan): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins and superadmins can delete plans.', 403);
        }

        if ($plan->activeSubscriptions()->exists()) {
            return ApiResponse::error('Cannot delete plan with active subscriptions', 400);
        }

        try {
            $this->planService->deactivatePlan($plan);
            
            return ApiResponse::success([], 'Plan deactivated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to deactivate plan: ' . $e->getMessage(), 500);
        }
    }

    public function activate(Request $request, Plan $plan): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins and superadmins can activate plans.', 403);
        }

        try {
            $activatedPlan = $this->planService->activatePlan($plan);
            
            return ApiResponse::success([
                'plan' => new PlanResource($activatedPlan)
            ], 'Plan activated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to activate plan: ' . $e->getMessage(), 500);
        }
    }

    public function sync(Request $request, Plan $plan): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins and superadmins can sync plans.', 403);
        }

        try {
            $syncedPlan = $this->planService->syncWithPaystack($plan);
            
            return ApiResponse::success([
                'plan' => new PlanResource($syncedPlan)
            ], 'Plan synced with Paystack successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to sync plan: ' . $e->getMessage(), 500);
        }
    }
}
