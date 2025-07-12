<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoice extends Model
{
    protected $fillable = [
        'subscription_id',
        'invoice_code',
        'amount',
        'currency',
        'status',
        'paid',
        'paid_at',
        'period_start',
        'period_end',
        'description',
        'transaction_reference',
        'authorization_data',
        'metadata',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'paid_at' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'authorization_data' => 'array',
        'metadata' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isPaid(): bool
    {
        return $this->paid && $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed' || $this->status === 'attention';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2);
    }

    public function scopePaid($query)
    {
        return $query->where('paid', true)->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'attention']);
    }

    public function scopeForSubscription($query, $subscriptionId)
    {
        return $query->where('subscription_id', $subscriptionId);
    }
}
