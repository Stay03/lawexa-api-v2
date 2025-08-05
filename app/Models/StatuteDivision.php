<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StatuteDivision extends Model
{
    protected $fillable = [
        'slug', 'statute_id', 'parent_division_id', 'division_type',
        'division_number', 'division_title', 'division_subtitle',
        'content', 'sort_order', 'level', 'status', 'effective_date'
    ];

    protected $casts = [
        'effective_date' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($division) {
            if (empty($division->slug)) {
                $division->slug = Str::slug($division->division_title);
            }
        });

        static::updating(function ($division) {
            if ($division->isDirty('division_title')) {
                $division->slug = Str::slug($division->division_title);
            }
        });
    }

    public function statute(): BelongsTo
    {
        return $this->belongsTo(Statute::class);
    }

    public function parentDivision(): BelongsTo
    {
        return $this->belongsTo(StatuteDivision::class, 'parent_division_id');
    }

    public function childDivisions(): HasMany
    {
        return $this->hasMany(StatuteDivision::class, 'parent_division_id')->orderBy('sort_order');
    }

    public function provisions(): HasMany
    {
        return $this->hasMany(StatuteProvision::class, 'division_id')->orderBy('sort_order');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('division_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}