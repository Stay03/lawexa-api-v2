<?php

namespace App\Http\Controllers;

use App\Models\ContentRequest;
use App\Http\Requests\CreateContentRequestRequest;
use App\Http\Resources\ContentRequestResource;
use App\Http\Responses\ApiResponse;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContentRequestController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the user's content requests.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = ContentRequest::where('user_id', $user->id)
                ->with(['user', 'createdContent', 'statute', 'fulfilledBy', 'rejectedBy']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Search by title
            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Sort
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate
            $perPage = min($request->input('per_page', 15), 100);
            $contentRequests = $query->paginate($perPage);

            return ApiResponse::success([
                'content_requests' => ContentRequestResource::collection($contentRequests),
                'meta' => [
                    'current_page' => $contentRequests->currentPage(),
                    'last_page' => $contentRequests->lastPage(),
                    'per_page' => $contentRequests->perPage(),
                    'total' => $contentRequests->total(),
                    'from' => $contentRequests->firstItem(),
                    'to' => $contentRequests->lastItem(),
                ],
                'links' => [
                    'first' => $contentRequests->url(1),
                    'last' => $contentRequests->url($contentRequests->lastPage()),
                    'prev' => $contentRequests->previousPageUrl(),
                    'next' => $contentRequests->nextPageUrl(),
                ],
            ], 'Content requests retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving content requests: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving content requests', null, 500);
        }
    }

    /**
     * Store a newly created content request.
     *
     * @param CreateContentRequestRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateContentRequestRequest $request)
    {
        DB::beginTransaction();

        try {
            $contentRequest = ContentRequest::create([
                'user_id' => $request->user()->id,
                'type' => $request->type,
                'title' => $request->title,
                'additional_notes' => $request->additional_notes,
                'statute_id' => $request->statute_id,
                'parent_division_id' => $request->parent_division_id,
                'parent_provision_id' => $request->parent_provision_id,
                'status' => 'pending',
            ]);

            $contentRequest->load(['user', 'statute']);

            // Send email notifications (Phase 2 - not implemented yet)
            // $this->notificationService->sendContentRequestCreatedEmail(
            //     $request->user(),
            //     $contentRequest
            // );

            DB::commit();

            return ApiResponse::success(
                ['content_request' => new ContentRequestResource($contentRequest)],
                'Content request submitted successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while creating content request', null, 500);
        }
    }

    /**
     * Display the specified content request.
     *
     * @param Request $request
     * @param ContentRequest $contentRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, ContentRequest $contentRequest)
    {
        try {
            // Ensure user can only view their own requests
            if ($contentRequest->user_id !== $request->user()->id) {
                return ApiResponse::forbidden('You can only view your own content requests');
            }

            $contentRequest->load([
                'user',
                'createdContent',
                'statute',
                'parentDivision',
                'parentProvision',
                'fulfilledBy',
                'rejectedBy',
                'comments.user',
            ]);

            return ApiResponse::success(
                ['content_request' => new ContentRequestResource($contentRequest)],
                'Content request retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Error retrieving content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving content request', null, 500);
        }
    }

    /**
     * Remove the specified content request.
     * Only pending requests can be deleted by users.
     *
     * @param Request $request
     * @param ContentRequest $contentRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, ContentRequest $contentRequest)
    {
        try {
            // Ensure user can only delete their own requests
            if ($contentRequest->user_id !== $request->user()->id) {
                return ApiResponse::forbidden('You can only delete your own content requests');
            }

            // Only pending requests can be deleted
            if (!$contentRequest->canBeDeletedByUser()) {
                return ApiResponse::error(
                    'Only pending requests can be deleted',
                    null,
                    422
                );
            }

            $contentRequest->delete();

            return ApiResponse::success(
                null,
                'Content request deleted successfully'
            );

        } catch (\Exception $e) {
            Log::error('Error deleting content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while deleting content request', null, 500);
        }
    }
}
