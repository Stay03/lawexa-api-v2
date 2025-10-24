<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Feedback extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feedback';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'feedback_text',
        'content_type',
        'content_id',
        'page',
        'status',
        'resolved_by',
        'resolved_at',
        'moved_to_issues',
        'moved_by',
        'moved_at',
        'issue_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'resolved_at' => 'datetime',
        'moved_at' => 'datetime',
        'moved_to_issues' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who submitted the feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the content (polymorphic).
     * This could be a CourtCase, Statute, StatuteProvision, StatuteDivision, or Note.
     */
    public function content(): MorphTo
    {
        return $this->morphTo('content');
    }

    /**
     * Get the images associated with this feedback.
     */
    public function images(): HasMany
    {
        return $this->hasMany(FeedbackImage::class)->orderBy('order');
    }

    /**
     * Get the admin who resolved this feedback.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the admin who moved this feedback to issues.
     */
    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    /**
     * Get the issue that was created from this feedback.
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }

    /**
     * Scope: Filter pending feedback.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Filter under review feedback.
     */
    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    /**
     * Scope: Filter resolved feedback.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope: Filter feedback moved to issues.
     */
    public function scopeMovedToIssues($query)
    {
        return $query->where('moved_to_issues', true);
    }

    /**
     * Scope: Filter feedback for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by content type.
     */
    public function scopeOfContentType($query, string $contentType)
    {
        return $query->where('content_type', $contentType);
    }

    /**
     * Scope: Search feedback text.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('feedback_text', 'LIKE', "%{$search}%");
    }

    /**
     * Check if feedback is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if feedback is under review.
     */
    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    /**
     * Check if feedback is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if feedback has been moved to issues.
     */
    public function hasBeenMovedToIssues(): bool
    {
        return $this->moved_to_issues === true;
    }

    /**
     * Mark feedback as under review.
     */
    public function markAsUnderReview(): void
    {
        $this->update([
            'status' => 'under_review',
        ]);
    }

    /**
     * Mark feedback as resolved.
     */
    public function markAsResolved(int $resolvedById): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by' => $resolvedById,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Move feedback to issues.
     */
    public function moveToIssues(int $movedById): void
    {
        $this->update([
            'moved_to_issues' => true,
            'moved_by' => $movedById,
            'moved_at' => now(),
        ]);
    }

    /**
     * Check if feedback can be resolved.
     * Only pending or under_review feedback can be resolved.
     */
    public function canBeResolved(): bool
    {
        return in_array($this->status, ['pending', 'under_review']);
    }

    /**
     * Check if feedback can be moved to issues.
     */
    public function canBeMovedToIssues(): bool
    {
        return !$this->moved_to_issues;
    }

    /**
     * Get human-readable status name.
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'under_review' => 'Under Review',
            'resolved' => 'Resolved',
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
            'under_review' => 'blue',
            'resolved' => 'green',
            default => 'gray',
        };
    }

    /**
     * Get content type name.
     */
    public function getContentTypeNameAttribute(): ?string
    {
        if (!$this->content_type) {
            return null;
        }

        return match($this->content_type) {
            'App\Models\CourtCase' => 'Case',
            'App\Models\Statute' => 'Statute',
            'App\Models\StatuteProvision' => 'Provision',
            'App\Models\StatuteDivision' => 'Division',
            'App\Models\Note' => 'Note',
            default => class_basename($this->content_type),
        };
    }
}
