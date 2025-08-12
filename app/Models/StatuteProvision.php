<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StatuteProvision extends Model
{
    protected $fillable = [
        'slug', 'statute_id', 'division_id', 'parent_provision_id', 'provision_type',
        'provision_number', 'provision_title', 'provision_text', 'marginal_note',
        'interpretation_note', 'range', 'sort_order', 'level', 'status', 'effective_date'
    ];

    protected $casts = [
        'effective_date' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($provision) {
            if (empty($provision->slug)) {
                $title = $provision->provision_title ?: $provision->provision_number;
                $provision->slug = Str::slug($title);
            }
        });

        static::updating(function ($provision) {
            if ($provision->isDirty('provision_title') || $provision->isDirty('provision_number')) {
                $title = $provision->provision_title ?: $provision->provision_number;
                $provision->slug = Str::slug($title);
            }
        });
    }

    public function statute(): BelongsTo
    {
        return $this->belongsTo(Statute::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(StatuteDivision::class, 'division_id');
    }

    public function parentProvision(): BelongsTo
    {
        return $this->belongsTo(StatuteProvision::class, 'parent_provision_id');
    }

    public function childProvisions(): HasMany
    {
        return $this->hasMany(StatuteProvision::class, 'parent_provision_id')->orderBy('sort_order');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('provision_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('provision_title', 'like', "%{$search}%")
                    ->orWhere('provision_text', 'like', "%{$search}%")
                    ->orWhere('provision_number', 'like', "%{$search}%");
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}