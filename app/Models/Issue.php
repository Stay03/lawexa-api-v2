<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Issue extends Model
{
    protected $fillable = [
        'user_id',
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

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function screenshots(): MorphMany
    {
        return $this->files()->where('file_type', 'LIKE', 'image/%');
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
