<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CourtCase extends Model
{
    protected $fillable = [
        'title',
        'body',
        'report',
        'course',
        'topic',
        'tag',
        'principles',
        'level',
        'slug',
        'court',
        'date',
        'country',
        'citation',
        'judges',
        'judicial_precedent',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($case) {
            if (empty($case->slug)) {
                $case->slug = Str::slug($case->title);
            }
        });

        static::updating(function ($case) {
            if ($case->isDirty('title')) {
                $case->slug = Str::slug($case->title);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByCourt($query, $court)
    {
        return $query->where('court', $court);
    }

    public function scopeByTopic($query, $topic)
    {
        return $query->where('topic', $topic);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('court', 'like', "%{$search}%")
                    ->orWhere('citation', 'like', "%{$search}%");
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
