<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCaseRequest;
use App\Http\Requests\UpdateCaseRequest;
use App\Http\Resources\CaseCollection;
use App\Http\Resources\CaseResource;
use App\Http\Responses\ApiResponse;
use App\Models\CourtCase;
use App\Traits\HandlesDirectS3Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCaseController extends Controller
{
    use HandlesDirectS3Uploads;
    public function index(Request $request): JsonResponse
    {
        $includeSimilarCases = $request->boolean('include_similar_cases', false);
        
        $with = ['creator:id,name', 'files'];
        if ($includeSimilarCases) {
            $with[] = 'similarCases:id,title,slug,court,date,country,citation';
            $with[] = 'casesWhereThisIsSimilar:id,title,slug,court,date,country,citation';
        }
        
        $query = CourtCase::with($with);

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('country')) {
            $query->byCountry($request->country);
        }

        if ($request->has('court')) {
            $query->byCourt($request->court);
        }

        if ($request->has('topic')) {
            $query->byTopic($request->topic);
        }

        if ($request->has('level')) {
            $query->byLevel($request->level);
        }

        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->has('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        $cases = $query->latest()
                      ->paginate($request->get('per_page', 15));

        $caseCollection = new CaseCollection($cases);
        
        return ApiResponse::success(
            $caseCollection->toArray($request),
            'Cases retrieved successfully'
        );
    }

    public function store(CreateCaseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        try {
            $case = CourtCase::create($validated);
            
            // Handle similar cases if present
            if ($request->has('similar_case_ids') && is_array($request->similar_case_ids)) {
                $this->syncSimilarCases($case, $request->similar_case_ids);
            }
            
            // Handle file uploads if present
            if ($request->hasFile('files')) {
                $this->handleDirectS3FileUploads($request, $case, 'files', 'case_reports', $request->user()->id);
            }
            
            $case->load([
                'creator:id,name', 
                'files',
                'similarCases:id,title,slug,court,date,country,citation',
                'casesWhereThisIsSimilar:id,title,slug,court,date,country,citation'
            ]);

            return ApiResponse::success([
                'case' => new CaseResource($case)
            ], 'Case created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create case: ' . $e->getMessage(), 500);
        }
    }

    public function show($id): JsonResponse
    {
        $case = CourtCase::with([
            'creator:id,name', 
            'files',
            'similarCases:id,title,slug,court,date,country,citation',
            'casesWhereThisIsSimilar:id,title,slug,court,date,country,citation'
        ])->findOrFail($id);
        
        return ApiResponse::success([
            'case' => new CaseResource($case)
        ], 'Case retrieved successfully');
    }

    public function update(UpdateCaseRequest $request, $id): JsonResponse
    {
        $case = CourtCase::findOrFail($id);
        $validated = $request->validated();

        try {
            $case->update($validated);
            
            // Handle similar cases if present
            if ($request->has('similar_case_ids')) {
                $similarCaseIds = is_array($request->similar_case_ids) ? $request->similar_case_ids : [];
                $this->syncSimilarCases($case, $similarCaseIds);
            }
            
            // Handle file uploads if present
            if ($request->hasFile('files')) {
                $this->handleDirectS3FileUploads($request, $case, 'files', 'case_reports', $request->user()->id);
            }
            
            $case->load([
                'creator:id,name', 
                'files',
                'similarCases:id,title,slug,court,date,country,citation',
                'casesWhereThisIsSimilar:id,title,slug,court,date,country,citation'
            ]);

            return ApiResponse::success([
                'case' => new CaseResource($case)
            ], 'Case updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update case: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $case = CourtCase::findOrFail($id);
        
        try {
            // Delete associated files first
            $this->deleteDirectS3ModelFiles($case);
            
            $case->delete();

            return ApiResponse::success([], 'Case deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete case: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Sync similar cases for a given case
     */
    private function syncSimilarCases(CourtCase $case, array $similarCaseIds): void
    {
        // Remove the case itself from the list to prevent self-referencing
        $similarCaseIds = array_filter($similarCaseIds, function($id) use ($case) {
            return $id != $case->id;
        });

        // Remove duplicates
        $similarCaseIds = array_unique($similarCaseIds);

        // Sync the relationships - this will add new ones and remove old ones
        $case->similarCases()->sync($similarCaseIds);
    }
}