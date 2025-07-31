<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitedCase extends Model
{
    protected $fillable = [
        'case_id',
        'cited_case_id',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function citedCase(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'cited_case_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($citedCase) {
            if ($citedCase->case_id === $citedCase->cited_case_id) {
                throw new \InvalidArgumentException('A case cannot cite itself.');
            }
        });

        static::updating(function ($citedCase) {
            if ($citedCase->case_id === $citedCase->cited_case_id) {
                throw new \InvalidArgumentException('A case cannot cite itself.');
            }
        });
    }
}