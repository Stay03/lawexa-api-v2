<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentCollection;
use App\Http\Resources\CommentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Comment;
use Illuminate\Http\Request;

class AdminCommentController extends Controller
{
    public function index(Request $request)
    {
        $query = Comment::with(['user', 'commentable', 'parent']);

        if ($request->has('commentable_type')) {
            $query->where('commentable_type', $request->input('commentable_type'));
        }

        if ($request->has('commentable_id')) {
            $query->where('commentable_id', $request->input('commentable_id'));
        }

        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('content', 'like', "%{$search}%");
        }

        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        $comments = $query->paginate($request->input('per_page', 15));

        return ApiResponse::success([
            'comments' => new CommentCollection($comments)
        ], 'Comments retrieved successfully');
    }

    public function show(Comment $comment)
    {
        return ApiResponse::success([
            'comment' => new CommentResource($comment->load(['user', 'commentable', 'parent', 'replies.user']))
        ], 'Comment retrieved successfully');
    }

    public function approve(Comment $comment)
    {
        $comment->approve();

        return ApiResponse::success([
            'comment' => new CommentResource($comment->fresh())
        ], 'Comment approved successfully');
    }

    public function reject(Comment $comment)
    {
        $comment->reject();

        return ApiResponse::success([
            'comment' => new CommentResource($comment->fresh())
        ], 'Comment rejected successfully');
    }

    public function destroy(Comment $comment)
    {
        $comment->forceDelete();

        return ApiResponse::success(null, 'Comment permanently deleted successfully');
    }

    public function stats()
    {
        $totalComments = Comment::count();
        $approvedComments = Comment::where('is_approved', true)->count();
        $pendingComments = Comment::where('is_approved', false)->count();
        $commentsToday = Comment::whereDate('created_at', today())->count();
        $commentsThisWeek = Comment::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        $commentsByType = Comment::selectRaw('commentable_type, COUNT(*) as count')
            ->groupBy('commentable_type')
            ->get();

        return ApiResponse::success([
            'stats' => [
                'total_comments' => $totalComments,
                'approved_comments' => $approvedComments,
                'pending_comments' => $pendingComments,
                'comments_today' => $commentsToday,
                'comments_this_week' => $commentsThisWeek,
                'comments_by_type' => $commentsByType,
            ]
        ], 'Comment statistics retrieved successfully');
    }
}
