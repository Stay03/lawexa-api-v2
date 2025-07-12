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
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}