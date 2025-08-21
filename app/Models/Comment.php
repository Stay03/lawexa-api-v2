<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Comment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'commentable_id',
        'commentable_type',
        'parent_id',
        'content',
        'is_approved',
        'is_edited',
        'edited_at',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function attachments(): MorphMany
    {
        return $this->files();
    }

    public function images(): MorphMany
    {
        return $this->files()->whereRaw('mime_type LIKE "image/%"');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopeRootComments(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReplies(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeForCommentable(Builder $query, $commentableType, $commentableId): Builder
    {
        // Handle both short format (Issue) and full format (App\Models\Issue)
        $fullType = str_contains($commentableType, '\\') ? $commentableType : 'App\\Models\\' . $commentableType;
        $shortType = str_contains($commentableType, '\\') ? class_basename($commentableType) : $commentableType;
        
        return $query->where(function ($q) use ($fullType, $shortType) {
                    $q->where('commentable_type', $fullType)
                      ->orWhere('commentable_type', $shortType);
                })
                ->where('commentable_id', $commentableId);
    }

    public function isRootComment(): bool
    {
        return $this->parent_id === null;
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->isOwnedBy($user) || $user->hasAdminAccess();
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->isOwnedBy($user) || $user->hasAdminAccess();
    }

    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    public function approve(): void
    {
        $this->update(['is_approved' => true]);
    }

    public function reject(): void
    {
        $this->update(['is_approved' => false]);
    }
}
