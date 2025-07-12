<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'subscription_code',
        'email_token',
        'status',
        'quantity',
        'amount',
        'currency',
        'start_date',
        'next_payment_date',
        'cron_expression',
        'authorization_code',
        'authorization_data',
        'invoice_limit',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'next_payment_date' => 'datetime',
        'authorization_data' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->next_payment_date && 
               $this->next_payment_date->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->next_payment_date && 
               $this->next_payment_date->isPast() && 
               $this->status !== 'completed';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['active', 'attention']);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
