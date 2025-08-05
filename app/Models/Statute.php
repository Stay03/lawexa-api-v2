<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Statute extends Model
{
    protected $fillable = [
        'slug', 'title', 'short_title', 'year_enacted', 'commencement_date',
        'status', 'repealed_date', 'repealing_statute_id', 'parent_statute_id',
        'jurisdiction', 'country', 'state', 'local_government',
        'citation_format', 'sector', 'tags', 'description', 'range', 'created_by'
    ];

    protected $casts = [
        'tags' => 'array',
        'commencement_date' => 'date',
        'repealed_date' => 'date',
        'year_enacted' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($statute) {
            if (empty($statute->slug)) {
                $statute->slug = Str::slug($statute->title);
            }
        });

        static::updating(function ($statute) {
            if ($statute->isDirty('title')) {
                $statute->slug = Str::slug($statute->title);
            }
        });
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(StatuteDivision::class)->orderBy('sort_order');
    }

    public function provisions(): HasMany
    {
        return $this->hasMany(StatuteProvision::class)->orderBy('sort_order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StatuteSchedule::class)->orderBy('sort_order');
    }

    public function parentStatute(): BelongsTo
    {
        return $this->belongsTo(Statute::class, 'parent_statute_id');
    }

    public function childStatutes(): HasMany
    {
        return $this->hasMany(Statute::class, 'parent_statute_id');
    }

    public function repealingStatute(): BelongsTo
    {
        return $this->belongsTo(Statute::class, 'repealing_statute_id');
    }

    public function repealedStatutes(): HasMany
    {
        return $this->hasMany(Statute::class, 'repealing_statute_id');
    }

    public function amendments(): BelongsToMany
    {
        return $this->belongsToMany(Statute::class, 'statute_amendments', 'original_statute_id', 'amending_statute_id')
                    ->withPivot(['effective_date', 'amendment_description'])
                    ->withTimestamps();
    }

    public function amendedBy(): BelongsToMany
    {
        return $this->belongsToMany(Statute::class, 'statute_amendments', 'amending_statute_id', 'original_statute_id')
                    ->withPivot(['effective_date', 'amendment_description'])
                    ->withTimestamps();
    }

    public function citedStatutes(): BelongsToMany
    {
        return $this->belongsToMany(Statute::class, 'statute_citations', 'citing_statute_id', 'cited_statute_id')
                    ->withPivot(['citation_context'])
                    ->withTimestamps();
    }

    public function citingStatutes(): BelongsToMany
    {
        return $this->belongsToMany(Statute::class, 'statute_citations', 'cited_statute_id', 'citing_statute_id')
                    ->withPivot(['citation_context'])
                    ->withTimestamps();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    // Query Scopes
    public function scopeSearch($query, $search)
    {
        return $query->where('title', 'like', "%{$search}%")
                    ->orWhere('short_title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('citation_format', 'like', "%{$search}%")
                    ->orWhereJsonContains('tags', $search);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByJurisdiction($query, $jurisdiction)
    {
        return $query->where('jurisdiction', $jurisdiction);
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByState($query, $state)
    {
        return $query->where('state', $state);
    }

    public function scopeBySector($query, $sector)
    {
        return $query->where('sector', $sector);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year_enacted', $year);
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