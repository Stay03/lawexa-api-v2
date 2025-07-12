<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
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
            'subscription_code' => $this->subscription_code,
            'status' => $this->status,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'start_date' => $this->start_date,
            'next_payment_date' => $this->next_payment_date,
            'cron_expression' => $this->cron_expression,
            'invoice_limit' => $this->invoice_limit,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'user' => new UserResource($this->whenLoaded('user')),
            'invoices' => SubscriptionInvoiceResource::collection($this->whenLoaded('invoices')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
