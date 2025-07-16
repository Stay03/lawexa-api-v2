<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionInvoiceResource extends JsonResource
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
            'invoice_code' => $this->invoice_code,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'paid' => $this->paid,
            'paid_at' => $this->paid_at,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'description' => $this->description,
            'transaction_reference' => $this->transaction_reference,
            'is_paid' => $this->isPaid(),
            'is_failed' => $this->isFailed(),
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'user' => $this->whenLoaded('subscription.user', function () {
                return [
                    'id' => $this->subscription->user->id,
                    'name' => $this->subscription->user->name,
                    'email' => $this->subscription->user->email,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
