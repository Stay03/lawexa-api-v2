<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Http\Requests\CreateStatuteRequest;
use App\Http\Requests\UpdateStatuteRequest;
use App\Http\Resources\StatuteResource;
use App\Http\Resources\StatuteCollection;
use App\Http\Responses\ApiResponse;
use App\Traits\HandlesDirectS3Uploads;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminStatuteController extends Controller
{
    use HandlesDirectS3Uploads;
    
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        $query = Statute::with([
            'creator:id,name',
            'divisions'
        ]);
        
        // Admin can see all statuses
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        // Apply other filters
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        if ($request->has('jurisdiction')) {
            $query->byJurisdiction($request->jurisdiction);
        }
        
        if ($request->has('country')) {
            $query->byCountry($request->country);
        }
        
        if ($request->has('state')) {
            $query->byState($request->state);
        }
        
        if ($request->has('sector')) {
            $query->bySector($request->sector);
        }
        
        if ($request->has('year')) {
            $query->byYear($request->year);
        }

        if ($request->has('created_by')) {
            $query->where('created_by', $request->created_by);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $statutes = $query->paginate($perPage);
        
        return ApiResponse::success(
            new StatuteCollection($statutes),
            'Statutes retrieved successfully'
        );
    }
    
    public function store(CreateStatuteRequest $request): JsonResponse
    {
        try {
            $statute = Statute::create(array_merge(
                $request->validated(),
                ['created_by' => $request->user()->id]
            ));
            
            // Handle file uploads
            if ($request->hasFile('files')) {
                $this->handleDirectS3FileUploads(
                    $request, 
                    $statute, 
                    'files', 
                    'statute_documents', 
                    $request->user()->id
                );
            }
            
            $statute->load(['creator:id,name', 'files']);
            
            return ApiResponse::success([
                'statute' => new StatuteResource($statute)
            ], 'Statute created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create statute: ' . $e->getMessage(), 500);
        }
    }
    
    public function show($id): JsonResponse
    {
        $statute = Statute::with([
            'creator:id,name',
            'files',
            'divisions' => function ($query) {
                $query->whereNull('parent_division_id')
                      ->orderBy('sort_order')
                      ->with([
                          'childDivisions' => function ($childQuery) {
                              $childQuery->orderBy('sort_order')
                                        ->with('provisions.childProvisions');
                          },
                          'provisions' => function ($provisionQuery) {
                              $provisionQuery->whereNull('parent_provision_id')
                                            ->orderBy('sort_order')
                                            ->with('childProvisions');
                          }
                      ]);
            },
            'schedules',
            'parentStatute:id,title',
            'childStatutes:id,title',
            'amendments:id,title',
            'amendedBy:id,title',
            'citedStatutes:id,title',
            'citingStatutes:id,title'
        ])->findOrFail($id);
        
        return ApiResponse::success([
            'statute' => new StatuteResource($statute)
        ], 'Statute retrieved successfully');
    }
    
    public function update(UpdateStatuteRequest $request, $id): JsonResponse
    {
        try {
            $statute = Statute::findOrFail($id);
            $statute->update($request->validated());
            
            // Handle file uploads
            if ($request->hasFile('files')) {
                $this->handleDirectS3FileUploads(
                    $request, 
                    $statute, 
                    'files', 
                    'statute_documents', 
                    $request->user()->id
                );
            }
            
            $statute->load(['creator:id,name', 'files']);
            
            return ApiResponse::success([
                'statute' => new StatuteResource($statute)
            ], 'Statute updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update statute: ' . $e->getMessage(), 500);
        }
    }
    
    public function destroy($id): JsonResponse
    {
        try {
            $statute = Statute::findOrFail($id);
            
            // Delete associated files first
            $this->deleteDirectS3ModelFiles($statute);
            
            $statute->delete();

            return ApiResponse::success([], 'Statute deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete statute: ' . $e->getMessage(), 500);
        }
    }
    
    public function addAmendment(Request $request, $id): JsonResponse
    {
        $request->validate([
            'amending_statute_id' => 'required|exists:statutes,id',
            'effective_date' => 'required|date',
            'amendment_description' => 'nullable|string'
        ]);
        
        $statute = Statute::findOrFail($id);
        
        $statute->amendments()->attach($request->amending_statute_id, [
            'effective_date' => $request->effective_date,
            'amendment_description' => $request->amendment_description
        ]);
        
        return ApiResponse::success(
            null,
            'Amendment added successfully'
        );
    }
    
    public function removeAmendment(Request $request, $id, $amendmentId): JsonResponse
    {
        $statute = Statute::findOrFail($id);
        $statute->amendments()->detach($amendmentId);
        
        return ApiResponse::success(
            null,
            'Amendment removed successfully'
        );
    }
    
    public function addCitation(Request $request, $id): JsonResponse
    {
        $request->validate([
            'cited_statute_id' => 'required|exists:statutes,id',
            'citation_context' => 'nullable|string'
        ]);
        
        $statute = Statute::findOrFail($id);
        
        $statute->citedStatutes()->attach($request->cited_statute_id, [
            'citation_context' => $request->citation_context
        ]);
        
        return ApiResponse::success(
            null,
            'Citation added successfully'
        );
    }
    
    public function removeCitation(Request $request, $id, $citationId): JsonResponse
    {
        $statute = Statute::findOrFail($id);
        $statute->citedStatutes()->detach($citationId);
        
        return ApiResponse::success(
            null,
            'Citation removed successfully'
        );
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'statute_ids' => 'required|array|min:1',
            'statute_ids.*' => 'integer|exists:statutes,id',
            'updates' => 'required|array',
            'updates.status' => 'sometimes|in:active,repealed,amended,suspended',
            'updates.sector' => 'sometimes|string|max:100',
        ]);

        try {
            Statute::whereIn('id', $request->statute_ids)
                   ->update($request->updates);

            return ApiResponse::success(
                null,
                'Statutes updated successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update statutes: ' . $e->getMessage(), 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'statute_ids' => 'required|array|min:1',
            'statute_ids.*' => 'integer|exists:statutes,id',
        ]);

        try {
            $statutes = Statute::whereIn('id', $request->statute_ids)->get();
            
            foreach ($statutes as $statute) {
                $this->deleteDirectS3ModelFiles($statute);
                $statute->delete();
            }

            return ApiResponse::success(
                null,
                'Statutes deleted successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete statutes: ' . $e->getMessage(), 500);
        }
    }
}