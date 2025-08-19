<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Comment $comment): bool
    {
        return $comment->is_approved || $comment->isOwnedBy($user) || $user->hasAdminAccess();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->canBeEditedBy($user);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->canBeDeletedBy($user);
    }

    public function restore(User $user, Comment $comment): bool
    {
        return $user->hasAdminAccess();
    }

    public function forceDelete(User $user, Comment $comment): bool
    {
        return $user->hasAdminAccess();
    }

    public function approve(User $user, Comment $comment): bool
    {
        return $user->hasAdminAccess();
    }

    public function reject(User $user, Comment $comment): bool
    {
        return $user->hasAdminAccess();
    }
}
