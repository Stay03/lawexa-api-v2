<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Http\Responses\ApiResponse;
use App\Http\Resources\StatuteDivisionCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminStatuteDivisionController extends Controller
{
    public function index(Request $request, $statuteId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        
        $query = $statute->divisions()->topLevel()->with(['parentDivision:id,division_title']);
        
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        if ($request->has('division_type')) {
            $query->byType($request->division_type);
        }
        
        $divisions = $query->orderBy('sort_order')
                          ->paginate($request->get('per_page', 15));
        
        $divisionCollection = new StatuteDivisionCollection($divisions);
        
        return ApiResponse::success(
            $divisionCollection->toArray($request),
            'Statute divisions retrieved successfully'
        );
    }
    
    public function store(Request $request, $statuteId): JsonResponse
    {
        $validated = $request->validate([
            'division_type' => 'required|in:part,chapter,article,title,book,division,section,subsection',
            'division_number' => 'required|string|max:255',
            'division_title' => 'required|string|max:255',
            'division_subtitle' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'parent_division_id' => 'nullable|exists:statute_divisions,id',
            'sort_order' => 'sometimes|integer|min:0',
            'level' => 'sometimes|integer|min:1|max:10',
            'status' => 'sometimes|in:active,repealed,amended',
            'effective_date' => 'nullable|date'
        ]);
        
        $statute = Statute::findOrFail($statuteId);
        
        try {
            $division = $statute->divisions()->create($validated);
            
            return ApiResponse::success([
                'division' => $division
            ], 'Division created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create division: ' . $e->getMessage(), 500);
        }
    }
    
    public function show($statuteId, $divisionId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        $division = $statute->divisions()->with([
            'parentDivision:id,division_title',
            'childDivisions:id,parent_division_id,division_title,division_number',
            'provisions:id,division_id,provision_title,provision_number'
        ])->findOrFail($divisionId);
        
        return ApiResponse::success([
            'division' => $division
        ], 'Division retrieved successfully');
    }
    
    public function children(Request $request, $statuteId, $divisionId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        $parentDivision = $statute->divisions()->findOrFail($divisionId);
        
        // Get children divisions with their parent info loaded
        $query = StatuteDivision::where('parent_division_id', $divisionId)
                                ->with(['parentDivision:id,division_title,division_number']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        if ($request->has('division_type')) {
            $query->byType($request->division_type);
        }
        
        // Get children
        $children = $query->orderBy('sort_order')
                         ->get();
        
        // Build breadcrumb trail
        $breadcrumb = $this->buildBreadcrumb($parentDivision);
        
        return ApiResponse::success([
            'parent' => [
                'id' => $parentDivision->id,
                'title' => $parentDivision->division_title,
                'number' => $parentDivision->division_number,
                'type' => $parentDivision->division_type,
                'level' => $parentDivision->level,
                'breadcrumb' => $breadcrumb
            ],
            'children' => $children,
            'meta' => [
                'has_children' => $children->count() > 0,
                'child_level' => $parentDivision->level + 1,
                'statute_id' => $statuteId
            ]
        ], 'Division children retrieved successfully');
    }
    
    private function buildBreadcrumb(StatuteDivision $division): array
    {
        $breadcrumb = [];
        $current = $division;
        
        // Build breadcrumb from current division up to root
        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'title' => $current->division_title,
                'number' => $current->division_number,
                'type' => $current->division_type
            ]);
            
            $current = $current->parentDivision;
        }
        
        // Add statute as root
        array_unshift($breadcrumb, [
            'id' => $division->statute->id,
            'title' => $division->statute->title,
            'type' => 'statute'
        ]);
        
        return $breadcrumb;
    }
    
    public function update(Request $request, $statuteId, $divisionId): JsonResponse
    {
        $validated = $request->validate([
            'division_type' => 'sometimes|in:part,chapter,article,title,book,division,section,subsection',
            'division_number' => 'sometimes|string|max:255',
            'division_title' => 'sometimes|string|max:255',
            'division_subtitle' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'parent_division_id' => 'nullable|exists:statute_divisions,id',
            'sort_order' => 'sometimes|integer|min:0',
            'level' => 'sometimes|integer|min:1|max:10',
            'status' => 'sometimes|in:active,repealed,amended',
            'effective_date' => 'nullable|date'
        ]);
        
        $statute = Statute::findOrFail($statuteId);
        $division = $statute->divisions()->findOrFail($divisionId);
        
        try {
            $division->update($validated);
            
            return ApiResponse::success([
                'division' => $division
            ], 'Division updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update division: ' . $e->getMessage(), 500);
        }
    }
    
    public function destroy($statuteId, $divisionId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        $division = $statute->divisions()->findOrFail($divisionId);
        
        try {
            $division->delete();
            
            return ApiResponse::success([], 'Division deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete division: ' . $e->getMessage(), 500);
        }
    }
}