<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Issue;
use App\Models\File;
use App\Http\Requests\UpdateFeedbackStatusRequest;
use App\Http\Requests\MoveFeedbackToIssuesRequest;
use App\Http\Resources\FeedbackResource;
use App\Http\Resources\AdminIssueResource;
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
            $oldStatus = $feedback->status;
            $newStatus = $request->status;

            // If marking as resolved, record who resolved it
            if ($newStatus === 'resolved') {
                $feedback->markAsResolved($request->user()->id);
            } else {
                // Update status only
                $feedback->update(['status' => $newStatus]);
            }

            // Sync status with linked issue (Feedback → Issue)
            if ($feedback->issue_id && $oldStatus !== $newStatus) {
                $this->syncFeedbackStatusToIssue($feedback, $request->user()->id);
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

            // Load content relationship for description generation
            $feedback->load('content', 'user');

            // Create an Issue from the feedback
            $issue = Issue::create([
                'user_id' => $feedback->user_id,
                'feedback_id' => $feedback->id,
                'title' => $this->generateIssueTitle($feedback),
                'description' => $this->generateIssueDescription($feedback),
                'type' => $request->input('type', 'other'),
                'severity' => $request->input('severity', 'medium'),
                'priority' => $request->input('priority', 'medium'),
                'status' => $request->input('status', 'open'),
                'area' => $request->input('area'),
                'category' => $request->input('category'),
                'assigned_to' => $request->input('assigned_to'),
                'admin_notes' => $request->input('admin_notes'),
            ]);

            // Transfer feedback images to the Issue as File records
            if ($feedback->images()->exists()) {
                foreach ($feedback->images as $image) {
                    // Create a File record for the issue
                    File::create([
                        'fileable_type' => Issue::class,
                        'fileable_id' => $issue->id,
                        'user_id' => $feedback->user_id,
                        'original_name' => basename($image->s3_path),
                        's3_path' => $image->s3_path,
                        'mime_type' => 'image/jpeg', // Default, can be enhanced
                        'size' => 0, // Not tracked in FeedbackImage
                    ]);
                }
            }

            // Update feedback: mark as moved and link to the created issue
            $feedback->update([
                'moved_to_issues' => true,
                'moved_by' => $request->user()->id,
                'moved_at' => now(),
                'issue_id' => $issue->id,
            ]);

            $feedback->load([
                'user',
                'images',
                'content',
                'resolvedBy',
                'movedBy',
                'issue',
            ]);

            $issue->load(['user', 'feedback', 'files', 'assignedTo']);

            DB::commit();

            return ApiResponse::success(
                [
                    'feedback' => new FeedbackResource($feedback),
                    'issue' => new AdminIssueResource($issue),
                ],
                'Feedback moved to issues successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error moving feedback to issues: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while moving feedback to issues', null, 500);
        }
    }

    /**
     * Generate a concise title for the issue from feedback.
     *
     * @param Feedback $feedback
     * @return string
     */
    private function generateIssueTitle(Feedback $feedback): string
    {
        // Truncate feedback text to create a title
        $title = $feedback->feedback_text;

        // Remove extra whitespace and newlines
        $title = preg_replace('/\s+/', ' ', $title);

        // Truncate to 100 characters
        if (strlen($title) > 100) {
            $title = substr($title, 0, 97) . '...';
        }

        return trim($title);
    }

    /**
     * Generate enhanced description for the issue from feedback with context.
     *
     * @param Feedback $feedback
     * @return string
     */
    private function generateIssueDescription(Feedback $feedback): string
    {
        $description = "[User Feedback]\n";
        $description .= $feedback->feedback_text . "\n\n";

        // Add content context if available
        if ($feedback->content_type && $feedback->content) {
            $description .= "Related to: " . $feedback->content_type_name;

            $contentTitle = $feedback->content->title ?? $feedback->content->name ?? null;
            if ($contentTitle) {
                $description .= " - " . $contentTitle;
            }
            $description .= "\n";
        }

        // Add page context if available
        if ($feedback->page) {
            $description .= "Page: " . $feedback->page . "\n";
        }

        // Add user info
        if ($feedback->user) {
            $description .= "Submitted by: " . $feedback->user->name . " (" . $feedback->user->email . ")\n";
        }

        return trim($description);
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

    /**
     * Sync feedback status to linked issue (Feedback → Issue).
     *
     * @param Feedback $feedback
     * @param int $adminId
     * @return void
     */
    private function syncFeedbackStatusToIssue(Feedback $feedback, int $adminId): void
    {
        try {
            if (!$feedback->issue_id) {
                return;
            }

            $issue = Issue::find($feedback->issue_id);
            if (!$issue) {
                return;
            }

            // Map feedback status to issue status
            $issueStatus = match($feedback->status) {
                'resolved' => 'resolved',
                'under_review' => 'in_progress',
                'pending' => 'open',
                default => null,
            };

            if ($issueStatus) {
                $updateData = ['status' => $issueStatus];

                // Sync resolved_by and resolved_at for resolved status
                if ($issueStatus === 'resolved') {
                    $updateData['resolved_by'] = $feedback->resolved_by ?? $adminId;
                    $updateData['resolved_at'] = $feedback->resolved_at ?? now();
                }

                $issue->update($updateData);

                Log::info('Synced feedback status to issue', [
                    'feedback_id' => $feedback->id,
                    'issue_id' => $issue->id,
                    'feedback_status' => $feedback->status,
                    'issue_status' => $issueStatus,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync feedback status to issue: ' . $e->getMessage());
        }
    }
}
