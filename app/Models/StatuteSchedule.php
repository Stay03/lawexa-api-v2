<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StatuteSchedule extends Model
{
    protected $fillable = [
        'slug', 'statute_id', 'schedule_number', 'schedule_title',
        'content', 'schedule_type', 'sort_order', 'status', 'effective_date'
    ];

    protected $casts = [
        'effective_date' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($schedule) {
            if (empty($schedule->slug)) {
                $schedule->slug = Str::slug($schedule->schedule_title);
            }
        });

        static::updating(function ($schedule) {
            if ($schedule->isDirty('schedule_title')) {
                $schedule->slug = Str::slug($schedule->schedule_title);
            }
        });
    }

    public function statute(): BelongsTo
    {
        return $this->belongsTo(Statute::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('schedule_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('schedule_title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('schedule_number', 'like', "%{$search}%");
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}