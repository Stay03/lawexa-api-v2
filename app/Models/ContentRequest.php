<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\Commentable;

class ContentRequest extends Model
{
    use HasFactory, Commentable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'additional_notes',
        'created_content_type',
        'created_content_id',
        'statute_id',
        'parent_division_id',
        'parent_provision_id',
        'status',
        'fulfilled_by',
        'fulfilled_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fulfilled_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the created content (polymorphic).
     * This could be a CourtCase, Statute, StatuteProvision, or StatuteDivision.
     */
    public function createdContent(): MorphTo
    {
        return $this->morphTo('created_content');
    }

    /**
     * Get the statute (for provision/division requests).
     */
    public function statute(): BelongsTo
    {
        return $this->belongsTo(Statute::class, 'statute_id');
    }

    /**
     * Get the parent division (for nested divisions/provisions).
     */
    public function parentDivision(): BelongsTo
    {
        return $this->belongsTo(StatuteDivision::class, 'parent_division_id');
    }

    /**
     * Get the parent provision (for nested provisions).
     */
    public function parentProvision(): BelongsTo
    {
        return $this->belongsTo(StatuteProvision::class, 'parent_provision_id');
    }

    /**
     * Get the admin who fulfilled this request.
     */
    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    /**
     * Get the admin who rejected this request.
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Scope: Filter by request type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter case requests only.
     */
    public function scopeCases($query)
    {
        return $query->where('type', 'case');
    }

    /**
     * Scope: Filter statute requests only.
     */
    public function scopeStatutes($query)
    {
        return $query->where('type', 'statute');
    }

    /**
     * Scope: Filter provision requests only.
     */
    public function scopeProvisions($query)
    {
        return $query->where('type', 'provision');
    }

    /**
     * Scope: Filter division requests only.
     */
    public function scopeDivisions($query)
    {
        return $query->where('type', 'division');
    }

    /**
     * Scope: Filter pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Filter in-progress requests.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope: Filter fulfilled requests.
     */
    public function scopeFulfilled($query)
    {
        return $query->where('status', 'fulfilled');
    }

    /**
     * Scope: Filter rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope: Filter requests for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Search by title.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('title', 'LIKE', "%{$search}%");
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if request is fulfilled.
     */
    public function isFulfilled(): bool
    {
        return $this->status === 'fulfilled';
    }

    /**
     * Check if request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if user can delete this request.
     * Only pending requests can be deleted.
     */
    public function canBeDeletedByUser(): bool
    {
        return $this->isPending();
    }

    /**
     * Mark request as fulfilled.
     */
    public function markAsFulfilled($createdContent, int $fulfilledById): void
    {
        $this->update([
            'status' => 'fulfilled',
            'created_content_type' => get_class($createdContent),
            'created_content_id' => $createdContent->id,
            'fulfilled_by' => $fulfilledById,
            'fulfilled_at' => now(),
        ]);
    }

    /**
     * Mark request as in progress.
     */
    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
        ]);
    }

    /**
     * Mark request as rejected.
     */
    public function markAsRejected(int $rejectedById, ?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_by' => $rejectedById,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Get duplicate count for this request.
     * Checks for similar titles with same type and pending status.
     */
    public function getDuplicateCount(): int
    {
        return static::where('type', $this->type)
            ->where('status', 'pending')
            ->where('id', '!=', $this->id)
            ->where('title', 'LIKE', "%{$this->title}%")
            ->count();
    }

    /**
     * Get human-readable type name.
     */
    public function getTypeNameAttribute(): string
    {
        return ucfirst($this->type);
    }

    /**
     * Get human-readable status name.
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'fulfilled' => 'Fulfilled',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'in_progress' => 'blue',
            'fulfilled' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Check if user can edit this request.
     * Requests are immutable, so this always returns false.
     */
    public function canBeEditedByUser(): bool
    {
        return false;
    }
}
