<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Http\Resources\IssueCollection;
use App\Http\Resources\IssueResource;
use App\Http\Responses\ApiResponse;
use App\Models\Issue;
use App\Models\File;
use App\Services\NotificationService;
use App\Traits\HandlesDirectS3Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IssueController extends Controller
{
    use HandlesDirectS3Uploads;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Display a listing of the user's issues.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Issue::where('user_id', $user->id)
            ->with(['files', 'screenshots'])
            ->withCount(['comments']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }
        
        if ($request->has('area')) {
            $query->where('area', $request->area);
        }
        
        $issues = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));
        
        $issueCollection = new IssueCollection($issues);
        
        return ApiResponse::success(
            $issueCollection->toArray($request),
            'Issues retrieved successfully'
        );
    }

    /**
     * Store a newly created issue.
     */
    public function store(CreateIssueRequest $request)
    {
        $user = $request->user();
        
        DB::beginTransaction();
        
        try {
            $issue = Issue::create([
                'user_id' => $user->id,
                ...$request->validated()
            ]);
            
            // Handle direct file uploads if present
            if ($request->hasFile('files')) {
                $fileCategory = $request->get('file_category', 'issue');
                $this->handleDirectS3FileUploads($request, $issue, 'files', $fileCategory, $user->id);
            }
            
            // Handle existing file_ids approach (backwards compatibility)
            if ($request->has('file_ids') && !empty($request->file_ids)) {
                File::whereIn('id', $request->file_ids)
                    ->where('uploaded_by', $user->id)
                    ->update([
                        'fileable_id' => $issue->id,
                        'fileable_type' => Issue::class
                    ]);
            }
            
            $issue->load(['user', 'files', 'screenshots']);
            
            DB::commit();
            
            // Send email notifications
            $this->notificationService->sendIssueCreatedEmail($user, $issue);
            
            return ApiResponse::created(
                new IssueResource($issue),
                'Issue created successfully'
            );
            
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::error('Failed to create issue', null, 500);
        }
    }

    /**
     * Display the specified issue.
     */
    public function show(Request $request, Issue $issue)
    {
        if ($issue->user_id !== $request->user()->id) {
            return ApiResponse::forbidden('You can only view your own issues');
        }
        
        $issue->load(['user', 'files', 'screenshots', 'comments']);
        
        return ApiResponse::success(
            new IssueResource($issue),
            'Issue retrieved successfully'
        );
    }

    /**
     * Update the specified issue.
     */
    public function update(UpdateIssueRequest $request, Issue $issue)
    {
        DB::beginTransaction();
        
        try {
            // Track changes for email notification
            $originalAttributes = $issue->getOriginal();
            $changes = [];
            
            $issue->update($request->validated());
            
            if ($request->has('file_ids')) {
                File::where('fileable_type', Issue::class)
                    ->where('fileable_id', $issue->id)
                    ->update([
                        'fileable_id' => null,
                        'fileable_type' => null
                    ]);
                
                if (!empty($request->file_ids)) {
                    File::whereIn('id', $request->file_ids)
                        ->where('uploaded_by', $request->user()->id)
                        ->update([
                            'fileable_id' => $issue->id,
                            'fileable_type' => Issue::class
                        ]);
                }
            }
            
            // Detect changes for email notification
            $updatedAttributes = $issue->getAttributes();
            foreach (['title', 'status', 'description', 'type', 'severity', 'area'] as $field) {
                if (isset($originalAttributes[$field], $updatedAttributes[$field]) && 
                    $originalAttributes[$field] !== $updatedAttributes[$field]) {
                    $changes[$field] = [
                        'from' => $originalAttributes[$field],
                        'to' => $updatedAttributes[$field]
                    ];
                }
            }
            
            $issue->load(['user', 'files', 'screenshots']);
            
            DB::commit();
            
            // Send update notification if there are changes
            if (!empty($changes)) {
                $this->notificationService->sendIssueUpdatedEmail($issue->user, $issue, $changes);
            }
            
            return ApiResponse::success(
                new IssueResource($issue),
                'Issue updated successfully'
            );
            
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::error('Failed to update issue', null, 500);
        }
    }

    /**
     * Remove the specified issue from storage.
     */
    public function destroy(Request $request, Issue $issue)
    {
        if ($issue->user_id !== $request->user()->id) {
            return ApiResponse::forbidden('You can only delete your own issues');
        }
        
        $issue->delete();
        
        return ApiResponse::success(null, 'Issue deleted successfully');
    }
}
