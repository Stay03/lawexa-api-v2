<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'avatar' => $this->avatar,
            'google_id' => $this->google_id,
            'customer_code' => $this->customer_code,
            'email_verified' => $this->hasVerifiedEmail(),
            'subscription_status' => $this->subscription_status,
            'subscription_expiry' => $this->subscription_expiry,
            'has_active_subscription' => $this->hasActiveSubscription(),
            'plan' => $this->when(
                $this->relationLoaded('activeSubscription') && $this->activeSubscription && $this->activeSubscription->plan,
                fn() => $this->activeSubscription->plan->name
            ),
            'plan_code' => $this->when(
                $this->relationLoaded('activeSubscription') && $this->activeSubscription && $this->activeSubscription->plan,
                fn() => $this->activeSubscription->plan->plan_code
            ),
            'formatted_amount' => $this->when(
                $this->relationLoaded('activeSubscription') && $this->activeSubscription && $this->activeSubscription->plan,
                fn() => $this->activeSubscription->plan->formatted_amount
            ),
            'amount' => $this->when(
                $this->relationLoaded('activeSubscription') && $this->activeSubscription && $this->activeSubscription->plan,
                fn() => $this->activeSubscription->plan->amount
            ),
            'interval' => $this->when(
                $this->relationLoaded('activeSubscription') && $this->activeSubscription && $this->activeSubscription->plan,
                fn() => $this->activeSubscription->plan->interval
            ),
            'active_subscription' => $this->when(
                $this->relationLoaded('activeSubscription') && $this->activeSubscription, 
                fn() => new SubscriptionResource($this->activeSubscription)
            ),
            'subscriptions' => $this->when(
                $this->relationLoaded('subscriptions') && $this->subscriptions, 
                fn() => SubscriptionResource::collection($this->subscriptions)
            ),
            'view_stats' => $this->when(
                $this->isGuest(),
                fn() => [
                    'total_views' => $this->getTotalViewsCount(),
                    'remaining_views' => $this->getRemainingViews(),
                    'view_limit' => config('view_tracking.guest_limits.total_views', 20),
                    'limit_reached' => $this->hasReachedViewLimit()
                ]
            ),
            // Profile fields
            'profession' => $this->profession,
            'country' => $this->country,
            'area_of_expertise' => $this->area_of_expertise,
            'university' => $this->university,
            'level' => $this->level,
            'work_experience' => $this->work_experience,
            'formatted_profile' => $this->formatted_profile,
            'is_student' => $this->isStudent(),
            'is_lawyer' => $this->isLawyer(),
            'is_law_student' => $this->isLawStudent(),
            'has_work_experience' => $this->hasWorkExperience(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            // Geolocation data from registration
            'registration_location' => $this->when(
                $this->ip_country || $this->ip_city || $this->ip_region,
                fn() => array_filter([
                    'country' => $this->ip_country,
                    'country_code' => $this->ip_country_code,
                    'region' => $this->ip_region,
                    'city' => $this->ip_city,
                    'timezone' => $this->ip_timezone,
                    'continent' => $this->ip_continent,
                    'continent_code' => $this->ip_continent_code,
                ])
            ),
            'registration_device' => $this->when(
                $this->device_type || $this->device_platform || $this->device_browser,
                fn() => array_filter([
                    'type' => $this->device_type,
                    'platform' => $this->device_platform,
                    'browser' => $this->device_browser,
                ])
            ),
        ];
    }
}