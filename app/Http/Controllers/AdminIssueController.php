<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUpdateIssueRequest;
use App\Http\Resources\AdminIssueResource;
use App\Http\Resources\IssueCollection;
use App\Http\Responses\ApiResponse;
use App\Models\Issue;
use App\Models\File;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminIssueController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Display a listing of all issues for admin.
     */
    public function index(Request $request)
    {
        $query = Issue::with(['user', 'assignedTo', 'files', 'screenshots'])
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
        
        if ($request->has('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'LIKE', "%{$search}%")
                               ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $issues = $query->orderBy($sortBy, $sortOrder)->paginate($request->get('per_page', 20));
        
        $issueCollection = new IssueCollection($issues);
        
        return ApiResponse::success(
            $issueCollection->toArray($request),
            'Issues retrieved successfully'
        );
    }

    /**
     * Display the specified issue for admin.
     */
    public function show(Issue $adminIssue)
    {
        $adminIssue->load(['user', 'assignedTo', 'files', 'screenshots', 'comments']);
        $adminIssue->loadCount(['comments']);
        
        return ApiResponse::success(
            new AdminIssueResource($adminIssue),
            'Issue retrieved successfully'
        );
    }

    /**
     * Update the specified issue (admin only).
     */
    public function update(AdminUpdateIssueRequest $request, Issue $adminIssue)
    {
        DB::beginTransaction();
        
        try {
            // Track changes for email notification
            $originalAttributes = $adminIssue->getOriginal();
            $changes = [];
            
            $data = $request->validated();
            
            if (isset($data['status']) && $data['status'] === 'resolved' && !$adminIssue->resolved_at) {
                $data['resolved_at'] = now();
            }
            
            $adminIssue->update($data);
            
            // Detect changes for email notification
            $updatedAttributes = $adminIssue->getAttributes();
            foreach (['title', 'status', 'description', 'type', 'severity', 'area', 'assigned_to'] as $field) {
                if (isset($originalAttributes[$field], $updatedAttributes[$field]) && 
                    $originalAttributes[$field] !== $updatedAttributes[$field]) {
                    $changes[$field] = [
                        'from' => $originalAttributes[$field],
                        'to' => $updatedAttributes[$field]
                    ];
                }
            }
            
            if ($request->has('file_ids')) {
                File::where('fileable_type', Issue::class)
                    ->where('fileable_id', $adminIssue->id)
                    ->update([
                        'fileable_id' => null,
                        'fileable_type' => null
                    ]);
                
                if (!empty($request->file_ids)) {
                    File::whereIn('id', $request->file_ids)
                        ->update([
                            'fileable_id' => $adminIssue->id,
                            'fileable_type' => Issue::class
                        ]);
                }
            }
            
            $adminIssue->load(['user', 'assignedTo', 'files', 'screenshots']);
            
            DB::commit();
            
            // Send update notifications if there are changes
            if (!empty($changes)) {
                // 1. Notify the original issue submitter
                if ($adminIssue->user) {
                    $this->notificationService->sendIssueUpdatedEmail($adminIssue->user, $adminIssue, $changes);
                }
                
                // 2. Notify all admin accounts about the admin update
                $adminEmails = $this->notificationService->getAdminEmails();
                foreach ($adminEmails as $adminEmail) {
                    // Don't send duplicate email if the original user is also an admin
                    if (!$adminIssue->user || $adminEmail !== $adminIssue->user->email) {
                        \Illuminate\Support\Facades\Mail::to($adminEmail)
                            ->queue(new \App\Mail\IssueUpdatedEmail($adminIssue->user ?? new \App\Models\User([
                                'name' => 'System User',
                                'email' => 'system@lawexa.com'
                            ]), $adminIssue, array_merge($changes, [
                                'updated_by_admin' => 'This issue was updated by an administrator'
                            ])));
                    }
                }
                
                \Illuminate\Support\Facades\Log::info('Admin issue update - notifications sent', [
                    'issue_id' => $adminIssue->id,
                    'original_user_notified' => $adminIssue->user ? $adminIssue->user->email : null,
                    'admin_notifications_sent_to' => $adminEmails,
                    'changes_made' => array_keys($changes),
                    'total_notifications' => ($adminIssue->user ? 1 : 0) + count($adminEmails)
                ]);
            }
            
            return ApiResponse::success(
                new AdminIssueResource($adminIssue),
                'Issue updated successfully'
            );
            
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::error('Failed to update issue', null, 500);
        }
    }

    /**
     * Remove the specified issue from storage (admin only).
     */
    public function destroy(Issue $adminIssue)
    {
        $adminIssue->delete();
        
        return ApiResponse::success(null, 'Issue deleted successfully');
    }

    /**
     * Get issue statistics for admin dashboard.
     */
    public function stats()
    {
        $stats = [
            'total_issues' => Issue::count(),
            'open_issues' => Issue::where('status', 'open')->count(),
            'in_progress_issues' => Issue::where('status', 'in_progress')->count(),
            'resolved_issues' => Issue::where('status', 'resolved')->count(),
            'closed_issues' => Issue::where('status', 'closed')->count(),
            'duplicate_issues' => Issue::where('status', 'duplicate')->count(),
            'critical_issues' => Issue::where('severity', 'critical')->count(),
            'unassigned_issues' => Issue::whereNull('assigned_to')->count(),
            'issues_by_area' => Issue::select('area', DB::raw('count(*) as count'))
                ->whereNotNull('area')
                ->groupBy('area')
                ->get()
                ->pluck('count', 'area'),
            'issues_by_type' => Issue::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type'),
            'recent_issues' => Issue::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($issue) => new AdminIssueResource($issue)),
        ];
        
        return ApiResponse::success($stats, 'Issue statistics retrieved successfully');
    }

    /**
     * Generate AI analysis for an issue.
     */
    public function aiAnalyze(Issue $adminIssue)
    {
        try {
            $analysis = "AI Analysis for Issue #{$adminIssue->id}:\n\n";
            $analysis .= "Title: {$adminIssue->title}\n";
            $analysis .= "Type: {$adminIssue->type}\n";
            $analysis .= "Severity: {$adminIssue->severity}\n";
            $analysis .= "Area: {$adminIssue->area}\n\n";
            $analysis .= "Description Analysis:\n";
            $analysis .= "This appears to be a {$issue->type} issue affecting the {$issue->area} area. ";
            $analysis .= "Based on the severity level ({$adminIssue->severity}), ";
            
            switch ($adminIssue->severity) {
                case 'critical':
                    $analysis .= "this requires immediate attention and should be prioritized for urgent resolution.";
                    break;
                case 'high':
                    $analysis .= "this should be addressed in the next development cycle.";
                    break;
                case 'medium':
                    $analysis .= "this can be planned for an upcoming sprint.";
                    break;
                case 'low':
                    $analysis .= "this can be addressed when resources are available.";
                    break;
            }
            
            $analysis .= "\n\nRecommended Actions:\n";
            $analysis .= "1. Verify the issue reproduction steps\n";
            $analysis .= "2. Check similar past issues for patterns\n";
            $analysis .= "3. Assign to appropriate team member based on area\n";
            $analysis .= "4. Set up monitoring if this is a recurring issue\n";
            
            $adminIssue->update(['ai_analysis' => $analysis]);
            
            return ApiResponse::success(
                ['ai_analysis' => $analysis],
                'AI analysis completed successfully'
            );
            
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to generate AI analysis', null, 500);
        }
    }
}
