<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCommentRequest;
use App\Http\Requests\CreateReplyRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentCollection;
use App\Http\Resources\CommentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $commentableType = $request->input('commentable_type');
        $commentableId = $request->input('commentable_id');
        
        if (!$commentableType || !$commentableId) {
            return ApiResponse::error('Commentable type and ID are required', 400);
        }

        $comments = Comment::approved()
            ->forCommentable($commentableType, $commentableId)
            ->rootComments()
            ->with(['user', 'replies.user'])
            ->paginate(15);

        return ApiResponse::collection(
            new CommentCollection($comments),
            'Comments retrieved successfully'
        );
    }

    public function store(CreateCommentRequest $request)
    {
        $commentableType = $request->input('commentable_type');
        $commentableId = $request->input('commentable_id');
        
        // Normalize commentable_type to short format for consistency
        $commentableType = str_replace('App\\Models\\', '', $commentableType);
        
        $commentableClass = 'App\\Models\\' . $commentableType;
        
        if (!class_exists($commentableClass)) {
            return ApiResponse::error('Invalid commentable type', 400);
        }

        $commentable = $commentableClass::find($commentableId);
        
        if (!$commentable) {
            return ApiResponse::error('Commentable resource not found', 404);
        }

        $comment = $commentable->addComment(
            $request->input('content'),
            $request->user()->id,
            $request->input('parent_id')
        );

        return ApiResponse::created(
            new CommentResource($comment->load(['user', 'replies.user'])),
            'Comment created successfully'
        );
    }

    public function show(Comment $comment)
    {
        if (!$comment->is_approved) {
            return ApiResponse::error('Comment not found', 404);
        }

        return ApiResponse::success(
            new CommentResource($comment->load(['user', 'replies.user'])),
            'Comment retrieved successfully'
        );
    }

    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        if (!Gate::allows('update', $comment)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $originalContent = $comment->content;
        
        $comment->update([
            'content' => $request->input('content')
        ]);

        if ($originalContent !== $comment->content) {
            $comment->markAsEdited();
        }

        return ApiResponse::success(
            new CommentResource($comment->fresh()->load(['user', 'replies.user'])),
            'Comment updated successfully'
        );
    }

    public function destroy(Comment $comment)
    {
        if (!Gate::allows('delete', $comment)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $comment->delete();

        return ApiResponse::success(null, 'Comment deleted successfully');
    }

    public function reply(CreateReplyRequest $request, Comment $comment)
    {
        if (!$comment->is_approved) {
            return ApiResponse::error('Parent comment not found', 404);
        }

        $reply = Comment::create([
            'user_id' => $request->user()->id,
            'commentable_type' => $comment->commentable_type,
            'commentable_id' => $comment->commentable_id,
            'parent_id' => $comment->id,
            'content' => $request->input('content'),
            'is_approved' => true,
        ]);

        return ApiResponse::created(
            new CommentResource($reply->load(['user'])),
            'Reply created successfully'
        );
    }
}
