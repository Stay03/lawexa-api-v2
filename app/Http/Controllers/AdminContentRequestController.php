<?php

namespace App\Http\Controllers;

use App\Models\ContentRequest;
use App\Http\Requests\AdminUpdateContentRequestRequest;
use App\Http\Resources\ContentRequestResource;
use App\Http\Responses\ApiResponse;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminContentRequestController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of all content requests.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = ContentRequest::with([
                'user',
                'createdContent',
                'statute',
                'fulfilledBy',
                'rejectedBy'
            ]);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
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
            Log::error('Admin error retrieving content requests: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving content requests', null, 500);
        }
    }

    /**
     * Display the specified content request.
     *
     * @param ContentRequest $contentRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ContentRequest $contentRequest)
    {
        try {
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
            Log::error('Admin error retrieving content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving content request', null, 500);
        }
    }

    /**
     * Update the specified content request.
     *
     * @param AdminUpdateContentRequestRequest $request
     * @param ContentRequest $contentRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(AdminUpdateContentRequestRequest $request, ContentRequest $contentRequest)
    {
        DB::beginTransaction();

        try {
            $oldStatus = $contentRequest->status;
            $changes = [];

            // Update status
            if ($request->has('status') && $request->status !== $oldStatus) {
                $contentRequest->status = $request->status;
                $changes['status'] = ['from' => $oldStatus, 'to' => $request->status];
            }

            // Link created content
            if ($request->has('created_content_type') && $request->has('created_content_id')) {
                $model = $request->created_content_type;
                $createdContent = $model::find($request->created_content_id);

                if ($createdContent) {
                    $contentRequest->markAsFulfilled($createdContent, $request->user()->id);
                    $changes['fulfilled'] = true;
                }
            }

            // Handle rejection
            if ($request->status === 'rejected') {
                $contentRequest->markAsRejected(
                    $request->user()->id,
                    $request->rejection_reason
                );
                $changes['rejected'] = true;
            }

            // Handle in_progress
            if ($request->status === 'in_progress' && $oldStatus === 'pending') {
                $contentRequest->markAsInProgress();
            }

            $contentRequest->save();
            $contentRequest->load([
                'user',
                'createdContent',
                'fulfilledBy',
                'rejectedBy'
            ]);

            // Send appropriate email notification (Phase 2 - not implemented yet)
            // if ($changes) {
            //     if (isset($changes['fulfilled'])) {
            //         $this->notificationService->sendContentRequestFulfilledEmail(
            //             $contentRequest->user,
            //             $contentRequest
            //         );
            //     } elseif (isset($changes['rejected'])) {
            //         $this->notificationService->sendContentRequestRejectedEmail(
            //             $contentRequest->user,
            //             $contentRequest
            //         );
            //     } elseif (isset($changes['status'])) {
            //         $this->notificationService->sendContentRequestUpdatedEmail(
            //             $contentRequest->user,
            //             $contentRequest,
            //             $changes
            //         );
            //     }
            // }

            DB::commit();

            return ApiResponse::success(
                ['content_request' => new ContentRequestResource($contentRequest)],
                'Content request updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin error updating content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while updating content request', null, 500);
        }
    }

    /**
     * Remove the specified content request.
     *
     * @param ContentRequest $contentRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ContentRequest $contentRequest)
    {
        try {
            $contentRequest->delete();

            return ApiResponse::success(
                null,
                'Content request deleted successfully'
            );

        } catch (\Exception $e) {
            Log::error('Admin error deleting content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while deleting content request', null, 500);
        }
    }

    /**
     * Get statistics about content requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            $stats = [
                'total' => ContentRequest::count(),
                'by_status' => [
                    'pending' => ContentRequest::pending()->count(),
                    'in_progress' => ContentRequest::inProgress()->count(),
                    'fulfilled' => ContentRequest::fulfilled()->count(),
                    'rejected' => ContentRequest::rejected()->count(),
                ],
                'by_type' => [
                    'case' => ContentRequest::cases()->count(),
                    'statute' => ContentRequest::statutes()->count(),
                    'provision' => ContentRequest::provisions()->count(),
                    'division' => ContentRequest::divisions()->count(),
                ],
                'recent_activity' => [
                    'last_7_days' => ContentRequest::where('created_at', '>=', now()->subDays(7))->count(),
                    'last_30_days' => ContentRequest::where('created_at', '>=', now()->subDays(30))->count(),
                ],
                'fulfillment_rate' => $this->calculateFulfillmentRate(),
            ];

            return ApiResponse::success($stats, 'Statistics retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving content request stats: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving statistics', null, 500);
        }
    }

    /**
     * Get duplicate requests (same title, different users).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function duplicates(Request $request)
    {
        try {
            $duplicates = DB::table('content_requests')
                ->select('title', 'type', DB::raw('COUNT(*) as request_count'))
                ->groupBy('title', 'type')
                ->having('request_count', '>', 1)
                ->orderBy('request_count', 'desc')
                ->limit(50)
                ->get();

            $detailedDuplicates = $duplicates->map(function ($duplicate) {
                $requests = ContentRequest::where('title', $duplicate->title)
                    ->where('type', $duplicate->type)
                    ->with(['user'])
                    ->get();

                return [
                    'title' => $duplicate->title,
                    'type' => $duplicate->type,
                    'request_count' => $duplicate->request_count,
                    'requests' => ContentRequestResource::collection($requests),
                ];
            });

            return ApiResponse::success(
                ['duplicates' => $detailedDuplicates],
                'Duplicate requests retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Error retrieving duplicate requests: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving duplicates', null, 500);
        }
    }

    /**
     * Calculate fulfillment rate percentage.
     *
     * @return float
     */
    private function calculateFulfillmentRate(): float
    {
        $total = ContentRequest::count();

        if ($total === 0) {
            return 0.0;
        }

        $fulfilled = ContentRequest::fulfilled()->count();

        return round(($fulfilled / $total) * 100, 2);
    }
}
