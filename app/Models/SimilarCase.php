<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimilarCase extends Model
{
    protected $fillable = [
        'case_id',
        'similar_case_id',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function similarCase(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'similar_case_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($similarCase) {
            if ($similarCase->case_id === $similarCase->similar_case_id) {
                throw new \InvalidArgumentException('A case cannot be similar to itself.');
            }
        });

        static::updating(function ($similarCase) {
            if ($similarCase->case_id === $similarCase->similar_case_id) {
                throw new \InvalidArgumentException('A case cannot be similar to itself.');
            }
        });
    }
}
