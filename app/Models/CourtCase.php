<?php

namespace App\Models;

use App\Traits\HasViewTracking;
use App\Traits\Folderable;
use App\Traits\Bookmarkable;
use App\Services\CitationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class CourtCase extends Model
{
    use HasViewTracking, Folderable, Bookmarkable;
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

        // Auto-generate citation after case is created (when ID is available)
        static::created(function ($case) {
            // Skip if user manually provided a citation
            if (!empty($case->citation)) {
                return;
            }

            $citationService = new CitationService();
            $citation = $citationService->generateCitation($case);

            if ($citation) {
                // Update without triggering events to avoid infinite loop
                $case->updateQuietly([
                    'citation' => $citation,
                    'title' => $citationService->appendCitationToTitle($case->title, $citation),
                    'slug' => Str::slug($citationService->appendCitationToTitle($case->title, $citation))
                ]);
            }
        });

        // Regenerate citation when country, court, or date changes
        static::updated(function ($case) {
            // Check if country, court, or date changed
            $relevantFieldsChanged = $case->wasChanged('country') ||
                                    $case->wasChanged('court') ||
                                    $case->wasChanged('date');

            if (!$relevantFieldsChanged) {
                return;
            }

            // Skip if user manually updated the citation field in the same update
            if ($case->wasChanged('citation')) {
                return;
            }

            $citationService = new CitationService();

            // Remove old citation from title
            $cleanTitle = $citationService->removeCitationFromTitle($case->title);

            // Generate new citation
            $newCitation = $citationService->generateCitation($case);

            if ($newCitation) {
                // Update with new citation
                $newTitle = $citationService->appendCitationToTitle($cleanTitle, $newCitation);
                $case->updateQuietly([
                    'citation' => $newCitation,
                    'title' => $newTitle,
                    'slug' => Str::slug($newTitle)
                ]);
            } else {
                // Clear citation if requirements not met
                $case->updateQuietly([
                    'citation' => null,
                    'title' => $cleanTitle,
                    'slug' => Str::slug($cleanTitle)
                ]);
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

    public function citedCases(): BelongsToMany
    {
        return $this->belongsToMany(CourtCase::class, 'cited_cases', 'case_id', 'cited_case_id')
                    ->withTimestamps();
    }

    public function casesThatCiteThis(): BelongsToMany
    {
        return $this->belongsToMany(CourtCase::class, 'cited_cases', 'cited_case_id', 'case_id')
                    ->withTimestamps();
    }

    public function allCitedCases()
    {
        return $this->citedCases()->union($this->casesThatCiteThis());
    }

    /**
     * Get all content requests that were fulfilled by creating this case.
     */
    public function contentRequests(): MorphMany
    {
        return $this->morphMany(ContentRequest::class, 'created_content');
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
        return $query->whereRaw('LOWER(topic) = ?', [strtolower($topic)]);
    }

    public function scopeByTag($query, $tag)
    {
        return $query->whereRaw('LOWER(tag) LIKE ?', ['%' . strtolower($tag) . '%']);
    }

    public function scopeByCourse($query, $course)
    {
        return $query->whereRaw('LOWER(course) = ?', [strtolower($course)]);
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
