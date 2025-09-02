<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'role',
        'customer_code',
        'guest_expires_at',
        'last_activity_at',
        // Registration geo and device data (optional)
        'registration_ip_address',
        'registration_user_agent',
        'ip_country',
        'ip_country_code',
        'ip_continent',
        'ip_continent_code',
        'ip_region',
        'ip_city',
        'ip_timezone',
        'device_type',
        'device_platform',
        'device_browser',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'guest_expires_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isGuest(): bool
    {
        return $this->role === 'guest';
    }

    public function isGuestExpired(): bool
    {
        if (!$this->isGuest() || !$this->guest_expires_at) {
            return false;
        }
        return $this->guest_expires_at->isPast();
    }

    public function isGuestInactive(): bool
    {
        if (!$this->isGuest() || !$this->last_activity_at) {
            return true; // Consider guests without activity as inactive
        }
        return $this->last_activity_at->lt(now()->subDays(30));
    }

    public function shouldBeCleanedUp(): bool
    {
        return $this->isGuest() && ($this->isGuestExpired() || $this->isGuestInactive());
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isResearcher(): bool
    {
        return $this->role === 'researcher';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function hasAdminAccess(): bool
    {
        return in_array($this->role, ['admin', 'researcher', 'superadmin']);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    public function hasActiveSubscription(): bool
    {
        $activeSubscription = $this->activeSubscription;
        return $activeSubscription && $activeSubscription->isActive();
    }

    public function getSubscriptionStatusAttribute(): string
    {
        if ($this->hasActiveSubscription()) {
            return 'active';
        }

        $latestSubscription = $this->subscriptions()->latest()->first();
        
        if (!$latestSubscription) {
            return 'inactive';
        }

        if ($latestSubscription->isExpired()) {
            return 'expired';
        }

        return $latestSubscription->status;
    }

    public function getSubscriptionExpiryAttribute()
    {
        return $this->activeSubscription?->next_payment_date;
    }

    public function courtCases(): HasMany
    {
        return $this->hasMany(CourtCase::class, 'created_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function assignedIssues(): HasMany
    {
        return $this->hasMany(Issue::class, 'assigned_to');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /**
     * Get total views count for this user (used for guest limits).
     */
    public function getTotalViewsCount(): int
    {
        return \App\Models\ModelView::getTotalViewsForUser($this->id);
    }

    /**
     * Get remaining views for this guest user.
     */
    public function getRemainingViews(): int
    {
        if (!$this->isGuest()) {
            return PHP_INT_MAX; // Non-guests have unlimited views
        }
        
        return \App\Models\ModelView::getRemainingViewsForGuest($this->id);
    }

    /**
     * Check if this guest user can view more content.
     */
    public function canViewMore(): bool
    {
        if (!$this->isGuest()) {
            return true; // Non-guests can always view more
        }
        
        return \App\Models\ModelView::canGuestView($this->id);
    }

    /**
     * Check if this guest user has reached their view limit.
     */
    public function hasReachedViewLimit(): bool
    {
        return !$this->canViewMore();
    }

    /**
     * Get formatted location string for display purposes.
     */
    public function getFormattedLocationAttribute(): ?string
    {
        $parts = array_filter([
            $this->ip_city,
            $this->ip_region,
            $this->ip_country
        ]);
        
        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Get formatted device string for display purposes.
     */
    public function getFormattedDeviceAttribute(): ?string
    {
        $parts = array_filter([
            $this->device_browser,
            $this->device_platform,
            $this->device_type
        ]);
        
        return !empty($parts) ? implode(' on ', $parts) : null;
    }
}
