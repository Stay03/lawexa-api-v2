<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'plan_code' => $this->plan_code,
            'description' => $this->description,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'interval' => $this->interval,
            'invoice_limit' => $this->invoice_limit,
            'send_invoices' => $this->send_invoices,
            'send_sms' => $this->send_sms,
            'hosted_page' => $this->hosted_page,
            'is_active' => $this->is_active,
            'subscriptions_count' => $this->whenCounted('subscriptions'),
            'active_subscriptions_count' => $this->whenCounted('activeSubscriptions'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
