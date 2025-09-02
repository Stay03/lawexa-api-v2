<?php

namespace App\Models;

use App\Traits\Commentable;
use App\Traits\HasViewTracking;
use App\Traits\Folderable;
use App\Traits\Bookmarkable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Note extends Model
{
    use HasFactory, Commentable, HasViewTracking, Folderable, Bookmarkable;

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'is_private',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'tags' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_private', false);
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('is_private', true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAccessibleByUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('is_private', false);
        });
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    public function scopeWithTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeOrderByLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function isPublic(): bool
    {
        return !$this->is_private;
    }

    public function getTagsListAttribute(): string
    {
        return $this->tags ? implode(', ', $this->tags) : '';
    }
}
