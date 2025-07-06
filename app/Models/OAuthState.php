<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OAuthState extends Model
{
    protected $table = 'oauth_states';
    
    protected $fillable = [
        'code',
        'token',
        'user_data',
        'expires_at',
    ];

    protected $casts = [
        'user_data' => 'array',
        'expires_at' => 'datetime',
    ];

    public static function createWithCode(string $token, array $userData): self
    {
        return self::create([
            'code' => Str::random(32),
            'token' => $token,
            'user_data' => $userData,
            'expires_at' => now()->addMinutes(10), // 10 minute expiry
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function cleanExpired(): void
    {
        self::where('expires_at', '<', now())->delete();
    }
}
