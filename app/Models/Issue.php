<?php

namespace App\Models;

use App\Traits\Commentable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Issue extends Model
{
    use Commentable;
    protected $fillable = [
        'user_id',
        'feedback_id',
        'title',
        'description',
        'type',
        'severity',
        'priority',
        'status',
        'area',
        'category',
        'browser_info',
        'environment_info',
        'steps_to_reproduce',
        'expected_behavior',
        'actual_behavior',
        'assigned_to',
        'resolved_by',
        'resolved_at',
        'ai_analysis',
        'admin_notes',
    ];

    protected $casts = [
        'browser_info' => 'array',
        'environment_info' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'feedback_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function screenshots(): MorphMany
    {
        return $this->files()->where('mime_type', 'LIKE', 'image/%');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function isAssigned(): bool
    {
        return $this->assigned_to !== null;
    }
}
