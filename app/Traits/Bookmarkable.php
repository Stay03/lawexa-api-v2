<?php

namespace App\Traits;

use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Bookmarkable
{
    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    public function addBookmark(User $user): bool
    {
        if ($this->isBookmarkedByUser($user)) {
            return false;
        }

        $this->bookmarks()->create([
            'user_id' => $user->id,
        ]);

        return true;
    }

    public function removeBookmark(User $user): bool
    {
        return $this->bookmarks()
                   ->where('user_id', $user->id)
                   ->delete() > 0;
    }

    public function isBookmarkedByUser(User $user): bool
    {
        return $this->bookmarks()
                   ->where('user_id', $user->id)
                   ->exists();
    }

    public function isBookmarkedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // Check if user_bookmarks relationship is loaded (eager loaded via withUserBookmark)
        if ($this->relationLoaded('userBookmarks')) {
            return $this->userBookmarks->isNotEmpty();
        }

        // Fallback to database query if not eager loaded
        return $this->isBookmarkedByUser($user);
    }

    /**
     * Get the bookmark ID for a specific user
     */
    public function getBookmarkIdFor(?User $user): ?int
    {
        if (!$user) {
            return null;
        }

        // Check if user_bookmarks relationship is loaded (eager loaded via withUserBookmark)
        if ($this->relationLoaded('userBookmarks')) {
            return $this->userBookmarks->first()?->id;
        }

        // Fallback to database query if not eager loaded
        return $this->bookmarks()
                   ->where('user_id', $user->id)
                   ->value('id');
    }

    /**
     * Relationship to get bookmarks for a specific user
     * This is used for eager loading with scopeWithUserBookmark
     */
    public function userBookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    /**
     * Scope to eager load user-specific bookmark status
     */
    public function scopeWithUserBookmark($query, ?User $user)
    {
        if (!$user) {
            return $query;
        }

        return $query->with(['userBookmarks' => function ($q) use ($user) {
            $q->where('user_id', $user->id)->select('id', 'bookmarkable_type', 'bookmarkable_id', 'user_id');
        }]);
    }

    public function getBookmarksCount(): int
    {
        return $this->bookmarks()->count();
    }

    public function getBookmarksCountForUser(User $user): int
    {
        return $this->bookmarks()
                   ->where('user_id', $user->id)
                   ->count();
    }

    public function scopeBookmarkedByUser($query, User $user)
    {
        return $query->whereHas('bookmarks', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function scopeNotBookmarkedByUser($query, User $user)
    {
        return $query->whereDoesntHave('bookmarks', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function scopePopularBookmarks($query, $limit = 10)
    {
        return $query->withCount('bookmarks')
                    ->orderBy('bookmarks_count', 'desc')
                    ->limit($limit);
    }
}