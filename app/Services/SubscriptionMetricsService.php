<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionMetricsService
{
    private string $period;

    private Carbon $currentPeriodStart;

    private Carbon $currentPeriodEnd;

    private Carbon $previousPeriodStart;

    private Carbon $previousPeriodEnd;

    public function getDashboardMetrics(string $period = 'monthly'): array
    {
        $this->period = $period;
        $this->setPeriodDates();

        return [
            'financial_overview' => $this->getFinancialOverview(),
            'subscription_counts' => $this->getSubscriptionCounts(),
            'payment_health' => $this->getPaymentHealth(),
            'business_metrics' => $this->getBusinessMetrics(),
            'plan_performance' => $this->getPlanPerformance(),
            'meta' => [
                'period' => $this->getPeriodLabel(),
            ],
        ];
    }

    private function setPeriodDates(): void
    {
        $now = Carbon::now();

        match ($this->period) {
            'daily' => [
                $this->currentPeriodStart = $now->copy()->startOfDay(),
                $this->currentPeriodEnd = $now->copy()->endOfDay(),
                $this->previousPeriodStart = $now->copy()->subDay()->startOfDay(),
                $this->previousPeriodEnd = $now->copy()->subDay()->endOfDay(),
            ],
            'weekly' => [
                $this->currentPeriodStart = $now->copy()->startOfWeek(),
                $this->currentPeriodEnd = $now->copy()->endOfWeek(),
                $this->previousPeriodStart = $now->copy()->subWeek()->startOfWeek(),
                $this->previousPeriodEnd = $now->copy()->subWeek()->endOfWeek(),
            ],
            'monthly' => [
                $this->currentPeriodStart = $now->copy()->startOfMonth(),
                $this->currentPeriodEnd = $now->copy()->endOfMonth(),
                $this->previousPeriodStart = $now->copy()->subMonth()->startOfMonth(),
                $this->previousPeriodEnd = $now->copy()->subMonth()->endOfMonth(),
            ],
            'quarterly' => [
                $this->currentPeriodStart = $now->copy()->startOfQuarter(),
                $this->currentPeriodEnd = $now->copy()->endOfQuarter(),
                $this->previousPeriodStart = $now->copy()->subQuarter()->startOfQuarter(),
                $this->previousPeriodEnd = $now->copy()->subQuarter()->endOfQuarter(),
            ],
            'biannually' => [
                $this->currentPeriodStart = $now->copy()->month <= 6 ? $now->copy()->startOfYear() : $now->copy()->month(7)->startOfMonth(),
                $this->currentPeriodEnd = $now->copy()->month <= 6 ? $now->copy()->month(6)->endOfMonth() : $now->copy()->endOfYear(),
                $this->previousPeriodStart = $now->copy()->month <= 6 ? $now->copy()->subYear()->month(7)->startOfMonth() : $now->copy()->startOfYear(),
                $this->previousPeriodEnd = $now->copy()->month <= 6 ? $now->copy()->subYear()->endOfYear() : $now->copy()->month(6)->endOfMonth(),
            ],
            'annually' => [
                $this->currentPeriodStart = $now->copy()->startOfYear(),
                $this->currentPeriodEnd = $now->copy()->endOfYear(),
                $this->previousPeriodStart = $now->copy()->subYear()->startOfYear(),
                $this->previousPeriodEnd = $now->copy()->subYear()->endOfYear(),
            ],
            default => throw new \InvalidArgumentException("Invalid period: {$this->period}"),
        };
    }

    private function getPeriodLabel(): string
    {
        return match ($this->period) {
            'daily' => 'last_24_hours',
            'weekly' => 'last_7_days',
            'monthly' => 'last_30_days',
            'quarterly' => 'last_90_days',
            'biannually' => 'last_6_months',
            'annually' => 'last_12_months',
            default => 'custom_period',
        };
    }

    private function getFinancialOverview(): array
    {
        // Calculate total actual revenue (includes new + renewals)
        $currentRevenue = $this->calculateRecurringRevenue($this->currentPeriodStart, $this->currentPeriodEnd);
        $previousRevenue = $this->calculateRecurringRevenue($this->previousPeriodStart, $this->previousPeriodEnd);

        // Calculate revenue breakdown by source
        $newBusinessRevenue = $this->calculateNewBusinessRevenue($this->currentPeriodStart, $this->currentPeriodEnd);
        $renewalRevenue = $this->calculateRenewalRevenue($this->currentPeriodStart, $this->currentPeriodEnd);

        // Calculate current MRR for forward-looking metrics
        $currentMRR = $this->calculateCurrentMRR();

        $growthRate = $previousRevenue > 0
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
            : 0;

        $revenueKey = $this->getRevenueKeyName();

        return [
            $revenueKey => $currentRevenue,
            'revenue_growth_rate' => round($growthRate, 1),
            'new_business_revenue' => $newBusinessRevenue,
            'renewal_revenue' => $renewalRevenue,
            'current_mrr' => $currentMRR,
            'revenue_breakdown' => [
                'new_customers' => $newBusinessRevenue,
                'renewals' => $renewalRevenue,
                'total' => $currentRevenue,
            ],
        ];
    }

    private function getRevenueKeyName(): string
    {
        return match ($this->period) {
            'daily' => 'daily_recurring_revenue',
            'weekly' => 'weekly_recurring_revenue',
            'monthly' => 'monthly_recurring_revenue',
            'quarterly' => 'quarterly_recurring_revenue',
            'biannually' => 'biannual_recurring_revenue',
            'annually' => 'annual_recurring_revenue',
            default => 'recurring_revenue',
        };
    }

    private function getSubscriptionCounts(): array
    {
        $counts = Subscription::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'total' => array_sum($counts),
            'active' => $counts['active'] ?? 0,
            'attention' => $counts['attention'] ?? 0,
            'cancelled' => $counts['cancelled'] ?? 0,
            'completed' => $counts['completed'] ?? 0,
            'non_renewing' => $counts['non_renewing'] ?? 0,
        ];
    }

    private function getPaymentHealth(): array
    {
        $overdueCount = Subscription::where('next_payment_date', '<', Carbon::now())
            ->where('status', '!=', 'completed')
            ->count();

        $successRate = $this->calculatePaymentSuccessRate();
        $renewalsNext7Days = $this->getRenewalsInPeriod(7);
        $renewalsNext30Days = $this->getRenewalsInPeriod(30);

        return [
            'overdue_count' => $overdueCount,
            'success_rate' => $successRate,
            'renewals_next_7_days' => $renewalsNext7Days,
            'renewals_next_30_days' => $renewalsNext30Days,
        ];
    }

    private function getBusinessMetrics(): array
    {
        $churnRate = $this->calculateChurnRate();
        $growthRate = $this->calculateSubscriberGrowthRate();

        return [
            'churn_rate' => $churnRate,
            'subscriber_growth_rate' => $growthRate,
        ];
    }

    private function getPlanPerformance(): array
    {
        $plans = Plan::withCount(['subscriptions as subscriber_count' => function ($query) {
            $query->where('status', 'active');
        }])
            ->where('is_active', true)
            ->get();

        return $plans->map(function ($plan) {
            $growthRate = $this->calculatePlanGrowthRate($plan->id);

            return [
                'plan_name' => $plan->name,
                'subscriber_count' => $plan->subscriber_count,
                'growth_rate' => $growthRate,
                'interval' => $plan->interval,
            ];
        })->toArray();
    }

    private function calculateRecurringRevenue(Carbon $startDate, Carbon $endDate): int
    {
        // Calculate actual revenue from paid invoices (includes new subscriptions AND renewals)
        return SubscriptionInvoice::where('paid', true)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('amount');
    }

    private function calculateActualRevenue(Carbon $startDate, Carbon $endDate): int
    {
        // Same as recurring revenue - actual collected revenue from paid invoices
        return $this->calculateRecurringRevenue($startDate, $endDate);
    }

    private function calculateNewBusinessRevenue(Carbon $startDate, Carbon $endDate): int
    {
        // Revenue from new subscriptions (first-time customers only)
        $newSubscriptionIds = Subscription::whereBetween('created_at', [$startDate, $endDate])
            ->pluck('id');

        return SubscriptionInvoice::where('paid', true)
            ->whereIn('subscription_id', $newSubscriptionIds)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->where('description', 'like', '%initial%')
            ->sum('amount');
    }

    private function calculateRenewalRevenue(Carbon $startDate, Carbon $endDate): int
    {
        // Revenue from renewals (excluding initial payments)
        return SubscriptionInvoice::where('paid', true)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->where('description', 'not like', '%initial%')
            ->sum('amount');
    }

    private function calculateCurrentMRR(): int
    {
        // Monthly Recurring Revenue from all currently active subscriptions
        $activeSubscriptions = Subscription::where('status', 'active')
            ->with('plan')
            ->get();

        $totalMRR = 0;
        foreach ($activeSubscriptions as $subscription) {
            $monthlyAmount = $this->normalizeToMonthlyAmount($subscription->amount, $subscription->plan->interval);
            $totalMRR += $monthlyAmount;
        }

        return $totalMRR;
    }

    private function normalizeToMonthlyAmount(int $amount, string $interval): int
    {
        // Convert any billing interval to monthly equivalent
        return match ($interval) {
            'hourly' => $amount * 24 * 30, // 24 hours * 30 days
            'daily' => $amount * 30, // 30 days
            'weekly' => $amount * 4.33, // ~4.33 weeks per month
            'monthly' => $amount,
            'quarterly' => intval($amount / 3), // 3 months
            'biannually' => intval($amount / 6), // 6 months
            'annually' => intval($amount / 12), // 12 months
            default => $amount, // fallback to original amount
        };
    }

    private function calculatePaymentSuccessRate(): float
    {
        $totalInvoices = SubscriptionInvoice::whereBetween('created_at', [$this->currentPeriodStart, $this->currentPeriodEnd])
            ->count();

        if ($totalInvoices === 0) {
            return 0.0;
        }

        $successfulInvoices = SubscriptionInvoice::whereBetween('created_at', [$this->currentPeriodStart, $this->currentPeriodEnd])
            ->where('paid', true)
            ->where('status', 'success')
            ->count();

        return round(($successfulInvoices / $totalInvoices) * 100, 1);
    }

    private function getRenewalsInPeriod(int $days): int
    {
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays($days);

        return Subscription::where('status', 'active')
            ->whereBetween('next_payment_date', [$startDate, $endDate])
            ->count();
    }

    private function calculateChurnRate(): float
    {
        $activeAtStart = Subscription::where('status', 'active')
            ->where('created_at', '<', $this->currentPeriodStart)
            ->count();

        if ($activeAtStart === 0) {
            return 0.0;
        }

        $churned = Subscription::whereIn('status', ['cancelled', 'completed'])
            ->whereBetween('updated_at', [$this->currentPeriodStart, $this->currentPeriodEnd])
            ->count();

        return round(($churned / $activeAtStart) * 100, 1);
    }

    private function calculateSubscriberGrowthRate(): float
    {
        $currentCount = Subscription::where('status', 'active')
            ->where('created_at', '<=', $this->currentPeriodEnd)
            ->count();

        $previousCount = Subscription::where('status', 'active')
            ->where('created_at', '<=', $this->previousPeriodEnd)
            ->count();

        if ($previousCount === 0) {
            return 0.0;
        }

        return round((($currentCount - $previousCount) / $previousCount) * 100, 1);
    }

    private function calculatePlanGrowthRate(int $planId): float
    {
        $currentCount = Subscription::where('plan_id', $planId)
            ->where('status', 'active')
            ->where('created_at', '<=', $this->currentPeriodEnd)
            ->count();

        $previousCount = Subscription::where('plan_id', $planId)
            ->where('status', 'active')
            ->where('created_at', '<=', $this->previousPeriodEnd)
            ->count();

        if ($previousCount === 0) {
            return $currentCount > 0 ? 100.0 : 0.0;
        }

        return round((($currentCount - $previousCount) / $previousCount) * 100, 1);
    }
}
