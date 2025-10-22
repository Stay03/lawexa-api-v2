<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Http\Requests\CreateFeedbackRequest;
use App\Http\Resources\FeedbackResource;
use App\Http\Responses\ApiResponse;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    protected FeedbackService $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    /**
     * Display a listing of the user's feedback.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = Feedback::where('user_id', $user->id)
                ->with(['user', 'images', 'content', 'resolvedBy', 'movedBy']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by content type
            if ($request->has('content_type')) {
                $query->ofContentType($request->content_type);
            }

            // Search in feedback text
            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Filter by moved to issues
            if ($request->has('moved_to_issues')) {
                if ($request->boolean('moved_to_issues')) {
                    $query->movedToIssues();
                } else {
                    $query->where('moved_to_issues', false);
                }
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
            ], 'Feedback retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving feedback: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving feedback', null, 500);
        }
    }

    /**
     * Store a newly created feedback.
     *
     * @param CreateFeedbackRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateFeedbackRequest $request)
    {
        try {
            $data = [
                'user_id' => $request->user()->id,
                'feedback_text' => $request->feedback_text,
                'content_type' => $request->content_type,
                'content_id' => $request->content_id,
                'page' => $request->page,
            ];

            // Get images if uploaded
            $images = $request->hasFile('images') ? $request->file('images') : null;

            // Create feedback with images
            $feedback = $this->feedbackService->createFeedback($data, $images);

            return ApiResponse::success(
                ['feedback' => new FeedbackResource($feedback)],
                'Feedback submitted successfully',
                201
            );

        } catch (\Exception $e) {
            Log::error('Error creating feedback: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while submitting feedback', null, 500);
        }
    }

    /**
     * Display the specified feedback.
     *
     * @param Request $request
     * @param Feedback $feedback
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Feedback $feedback)
    {
        try {
            // Ensure user can only view their own feedback
            if ($feedback->user_id !== $request->user()->id) {
                return ApiResponse::forbidden('You can only view your own feedback');
            }

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
            Log::error('Error retrieving feedback: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving feedback', null, 500);
        }
    }
}
