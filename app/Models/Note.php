<?php

namespace App\Models;

use App\Traits\Commentable;
use App\Traits\HasViewTracking;
use App\Traits\Folderable;
use App\Traits\Bookmarkable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Note extends Model
{
    use HasFactory, Commentable, HasViewTracking, Folderable, Bookmarkable;

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'status',
        'is_private',
        'tags',
        'price_ngn',
        'price_usd',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'is_private' => 'boolean',
            'tags' => 'array',
            'price_ngn' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the videos associated with the note.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(NoteVideo::class)->orderBy('sort_order');
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
            // Own notes (any status)
            $q->where('user_id', $userId)
              // OR published public notes
              ->orWhere(function ($q2) {
                  $q2->where('status', 'published')
                     ->where('is_private', false);
              });
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
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

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function getTagsListAttribute(): string
    {
        return $this->tags ? implode(', ', $this->tags) : '';
    }

    /**
     * Check if the note is free (no pricing set or both prices are zero).
     */
    public function isFree(): bool
    {
        return (empty($this->price_ngn) || $this->price_ngn <= 0)
            && (empty($this->price_usd) || $this->price_usd <= 0);
    }

    /**
     * Check if the note is a paid note.
     */
    public function isPaid(): bool
    {
        return !$this->isFree();
    }

    /**
     * Get a preview of the content (first ~200 characters).
     */
    public function getContentPreview(int $length = 200): string
    {
        if (empty($this->content)) {
            return '';
        }

        // Strip HTML tags first
        $plainText = strip_tags($this->content);

        if (strlen($plainText) <= $length) {
            return $plainText;
        }

        return substr($plainText, 0, $length) . '...';
    }

    /**
     * Check if a user has access to the full content of this note.
     * For now, only the owner has access to paid notes.
     * Later, this will check purchased_notes table.
     */
    public function userHasAccess(?User $user): bool
    {
        // Free notes are accessible to everyone
        if ($this->isFree()) {
            return true;
        }

        // No user means no access to paid content
        if (!$user) {
            return false;
        }

        // Owner always has access
        if ($this->isOwnedBy($user)) {
            return true;
        }

        // TODO: Check note_purchases table when payment is implemented
        // return NotePurchase::where('user_id', $user->id)
        //     ->where('note_id', $this->id)
        //     ->exists();

        return false;
    }

    /**
     * Scope for free notes.
     */
    public function scopeFree(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('price_ngn')
              ->orWhere('price_ngn', '<=', 0);
        })->where(function ($q) {
            $q->whereNull('price_usd')
              ->orWhere('price_usd', '<=', 0);
        });
    }

    /**
     * Scope for paid notes.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('price_ngn', '>', 0)
              ->orWhere('price_usd', '>', 0);
        });
    }
}
