<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'plan_code',
        'description',
        'amount',
        'currency',
        'interval',
        'invoice_limit',
        'send_invoices',
        'send_sms',
        'hosted_page',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'send_invoices' => 'boolean',
        'send_sms' => 'boolean',
        'hosted_page' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2);
    }

    public function getAmountInKoboAttribute(): int
    {
        return $this->amount;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
