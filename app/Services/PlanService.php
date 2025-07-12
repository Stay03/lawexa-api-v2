<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;

class PlanService
{
    public function __construct(
        private PaystackService $paystackService
    ) {}

    public function createPlan(array $planData): Plan
    {
        $paystackResponse = $this->paystackService->createPlan($planData);
        $paystackPlan = $paystackResponse['data'];

        return Plan::create([
            'name' => $paystackPlan['name'],
            'plan_code' => $paystackPlan['plan_code'],
            'description' => $planData['description'] ?? null,
            'amount' => $paystackPlan['amount'],
            'currency' => $paystackPlan['currency'],
            'interval' => $paystackPlan['interval'],
            'invoice_limit' => $paystackPlan['invoice_limit'],
            'send_invoices' => $paystackPlan['send_invoices'],
            'send_sms' => $paystackPlan['send_sms'],
            'hosted_page' => $paystackPlan['hosted_page'],
            'is_active' => true,
            'metadata' => $paystackPlan,
        ]);
    }

    public function updatePlan(Plan $plan, array $updateData): Plan
    {
        $updateData['update_existing_subscriptions'] = $updateData['update_existing_subscriptions'] ?? false;
        
        $paystackResponse = $this->paystackService->updatePlan($plan->plan_code, $updateData);
        
        // Check if response has data key, otherwise use the whole response
        $paystackPlan = $paystackResponse['data'] ?? $paystackResponse;

        $plan->update([
            'name' => $paystackPlan['name'] ?? $updateData['name'] ?? $plan->name,
            'description' => $updateData['description'] ?? $plan->description,
            'amount' => $paystackPlan['amount'] ?? $updateData['amount'] ?? $plan->amount,
            'interval' => $paystackPlan['interval'] ?? $updateData['interval'] ?? $plan->interval,
            'invoice_limit' => $paystackPlan['invoice_limit'] ?? $updateData['invoice_limit'] ?? $plan->invoice_limit,
            'send_invoices' => $paystackPlan['send_invoices'] ?? $updateData['send_invoices'] ?? $plan->send_invoices,
            'send_sms' => $paystackPlan['send_sms'] ?? $updateData['send_sms'] ?? $plan->send_sms,
            'metadata' => $paystackPlan,
        ]);

        return $plan->refresh();
    }

    public function getAllPlans(): Collection
    {
        return Plan::active()->orderBy('amount')->get();
    }

    public function getPlan(int $planId): Plan
    {
        return Plan::findOrFail($planId);
    }

    public function getPlanByCode(string $planCode): Plan
    {
        return Plan::where('plan_code', $planCode)->firstOrFail();
    }

    public function deactivatePlan(Plan $plan): Plan
    {
        $plan->update(['is_active' => false]);
        return $plan;
    }

    public function activatePlan(Plan $plan): Plan
    {
        $plan->update(['is_active' => true]);
        return $plan;
    }

    public function syncWithPaystack(Plan $plan): Plan
    {
        $paystackResponse = $this->paystackService->fetchPlan($plan->plan_code);
        $paystackPlan = $paystackResponse['data'];

        $plan->update([
            'name' => $paystackPlan['name'],
            'amount' => $paystackPlan['amount'],
            'interval' => $paystackPlan['interval'],
            'invoice_limit' => $paystackPlan['invoice_limit'],
            'send_invoices' => $paystackPlan['send_invoices'],
            'send_sms' => $paystackPlan['send_sms'],
            'hosted_page' => $paystackPlan['hosted_page'],
            'metadata' => $paystackPlan,
        ]);

        return $plan->refresh();
    }

    public function getPlansWithStats(): Collection
    {
        return Plan::withCount(['subscriptions', 'activeSubscriptions'])
            ->orderBy('amount')
            ->get();
    }
}