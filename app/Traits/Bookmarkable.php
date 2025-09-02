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