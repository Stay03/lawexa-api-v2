<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class University extends Model
{
    protected $fillable = [
        'country_code',
        'country',
        'name',
        'website',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'university', 'name');
    }

    public function scopeByCountry($query, string $countryCode)
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}
