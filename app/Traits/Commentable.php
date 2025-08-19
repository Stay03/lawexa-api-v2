<?php

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Commentable
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
                    ->approved()
                    ->rootComments()
                    ->with(['user', 'replies.user'])
                    ->orderBy('created_at', 'desc');
    }

    public function allComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function rootComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
                    ->approved()
                    ->rootComments()
                    ->with(['user'])
                    ->orderBy('created_at', 'desc');
    }

    public function commentCount(): int
    {
        return $this->allComments()->approved()->count();
    }

    public function rootCommentCount(): int
    {
        return $this->allComments()->approved()->rootComments()->count();
    }

    public function hasComments(): bool
    {
        return $this->commentCount() > 0;
    }

    public function addComment(string $content, int $userId, ?int $parentId = null): Comment
    {
        return $this->allComments()->create([
            'user_id' => $userId,
            'content' => $content,
            'parent_id' => $parentId,
            'is_approved' => true,
        ]);
    }
}