<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Http\Resources\IssueCollection;
use App\Http\Resources\IssueResource;
use App\Http\Responses\ApiResponse;
use App\Models\Issue;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IssueController extends Controller
{
    /**
     * Display a listing of the user's issues.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Issue::where('user_id', $user->id)
            ->with(['files', 'screenshots']);
        
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
        
        $issues = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return ApiResponse::collection(
            new IssueCollection($issues),
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
        
        $issue->load(['user', 'files', 'screenshots']);
        
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
            
            $issue->load(['user', 'files', 'screenshots']);
            
            DB::commit();
            
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
