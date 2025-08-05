<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminStatuteDivisionController extends Controller
{
    public function index(Request $request, $statuteId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        
        $query = $statute->divisions()->with(['parentDivision:id,division_title']);
        
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        if ($request->has('division_type')) {
            $query->byType($request->division_type);
        }
        
        $divisions = $query->orderBy('sort_order')
                          ->paginate($request->get('per_page', 50));
        
        return ApiResponse::success(
            $divisions,
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
            'childDivisions:id,division_title,division_number',
            'provisions:id,provision_title,provision_number'
        ])->findOrFail($divisionId);
        
        return ApiResponse::success([
            'division' => $division
        ], 'Division retrieved successfully');
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