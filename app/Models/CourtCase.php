<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function caseReport(): HasOne
    {
        return $this->hasOne(CaseReport::class, 'case_id');
    }

    public function similarCases(): BelongsToMany
    {
        return $this->belongsToMany(CourtCase::class, 'similar_cases', 'case_id', 'similar_case_id')
                    ->withTimestamps();
    }

    public function casesWhereThisIsSimilar(): BelongsToMany
    {
        return $this->belongsToMany(CourtCase::class, 'similar_cases', 'similar_case_id', 'case_id')
                    ->withTimestamps();
    }

    public function allSimilarCases()
    {
        return $this->similarCases()->union($this->casesWhereThisIsSimilar());
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
