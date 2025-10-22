<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Http\Requests\UpdateFeedbackStatusRequest;
use App\Http\Requests\MoveFeedbackToIssuesRequest;
use App\Http\Resources\FeedbackResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminFeedbackController extends Controller
{
    /**
     * Display a listing of all feedback (admin view).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Feedback::with(['user', 'images', 'content', 'resolvedBy', 'movedBy']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by content type
            if ($request->has('content_type')) {
                $query->ofContentType($request->content_type);
            }

            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by moved to issues
            if ($request->has('moved_to_issues')) {
                if ($request->boolean('moved_to_issues')) {
                    $query->movedToIssues();
                } else {
                    $query->where('moved_to_issues', false);
                }
            }

            // Search in feedback text
            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Date range filters
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sort
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate
            $perPage = min($request->input('per_page', 15), 100);
            $feedback = $query->paginate($perPage);

            return ApiResponse::success([
                'feedback' => FeedbackResource::collection($feedback),
                'meta' => [
                    'current_page' => $feedback->currentPage(),
                    'last_page' => $feedback->lastPage(),
                    'per_page' => $feedback->perPage(),
                    'total' => $feedback->total(),
                    'from' => $feedback->firstItem(),
                    'to' => $feedback->lastItem(),
                ],
                'links' => [
                    'first' => $feedback->url(1),
                    'last' => $feedback->url($feedback->lastPage()),
                    'prev' => $feedback->previousPageUrl(),
                    'next' => $feedback->nextPageUrl(),
                ],
                'stats' => $this->getStats($request),
            ], 'Feedback retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving feedback (admin): ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving feedback', null, 500);
        }
    }

    /**
     * Display the specified feedback (admin view).
     *
     * @param Feedback $feedback
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Feedback $feedback)
    {
        try {
            $feedback->load([
                'user',
                'images',
                'content',
                'resolvedBy',
                'movedBy',
            ]);

            return ApiResponse::success(
                ['feedback' => new FeedbackResource($feedback)],
                'Feedback retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Error retrieving feedback (admin): ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving feedback', null, 500);
        }
    }

    /**
     * Update feedback status.
     *
     * @param UpdateFeedbackStatusRequest $request
     * @param Feedback $feedback
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(UpdateFeedbackStatusRequest $request, Feedback $feedback)
    {
        DB::beginTransaction();

        try {
            $newStatus = $request->status;

            // If marking as resolved, record who resolved it
            if ($newStatus === 'resolved') {
                $feedback->markAsResolved($request->user()->id);
            } else {
                // Update status only
                $feedback->update(['status' => $newStatus]);
            }

            $feedback->load([
                'user',
                'images',
                'content',
                'resolvedBy',
                'movedBy',
            ]);

            DB::commit();

            return ApiResponse::success(
                ['feedback' => new FeedbackResource($feedback)],
                'Feedback status updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating feedback status: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while updating feedback status', null, 500);
        }
    }

    /**
     * Move feedback to issues.
     *
     * @param MoveFeedbackToIssuesRequest $request
     * @param Feedback $feedback
     * @return \Illuminate\Http\JsonResponse
     */
    public function moveToIssues(MoveFeedbackToIssuesRequest $request, Feedback $feedback)
    {
        DB::beginTransaction();

        try {
            // Check if already moved to issues
            if ($feedback->hasBeenMovedToIssues()) {
                return ApiResponse::error(
                    'This feedback has already been moved to issues',
                    null,
                    422
                );
            }

            // Move to issues
            $feedback->moveToIssues($request->user()->id);

            $feedback->load([
                'user',
                'images',
                'content',
                'resolvedBy',
                'movedBy',
            ]);

            DB::commit();

            return ApiResponse::success(
                ['feedback' => new FeedbackResource($feedback)],
                'Feedback moved to issues successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error moving feedback to issues: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while moving feedback to issues', null, 500);
        }
    }

    /**
     * Get feedback statistics.
     *
     * @param Request $request
     * @return array
     */
    private function getStats(Request $request): array
    {
        $query = Feedback::query();

        // Apply same filters as main query
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->pending()->count(),
            'under_review' => (clone $query)->underReview()->count(),
            'resolved' => (clone $query)->resolved()->count(),
            'moved_to_issues' => (clone $query)->movedToIssues()->count(),
        ];
    }
}
