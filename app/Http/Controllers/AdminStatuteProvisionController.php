<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Models\StatuteProvision;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminStatuteProvisionController extends Controller
{
    public function index(Request $request, $statuteId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        
        $query = $statute->provisions()->with(['division:id,division_title', 'parentProvision:id,provision_title']);
        
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        if ($request->has('provision_type')) {
            $query->byType($request->provision_type);
        }

        if ($request->has('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        $provisions = $query->orderBy('sort_order')
                           ->paginate($request->get('per_page', 100));
        
        return ApiResponse::success(
            $provisions,
            'Statute provisions retrieved successfully'
        );
    }
    
    public function store(Request $request, $statuteId): JsonResponse
    {
        $validated = $request->validate([
            'provision_type' => 'required|in:section,subsection,paragraph,subparagraph,clause,subclause,item',
            'provision_number' => 'required|string|max:255',
            'provision_title' => 'nullable|string|max:255',
            'provision_text' => 'required|string',
            'marginal_note' => 'nullable|string',
            'interpretation_note' => 'nullable|string',
            'division_id' => 'nullable|exists:statute_divisions,id',
            'parent_provision_id' => 'nullable|exists:statute_provisions,id',
            'sort_order' => 'sometimes|integer|min:0',
            'level' => 'sometimes|integer|min:1|max:10',
            'status' => 'sometimes|in:active,repealed,amended',
            'effective_date' => 'nullable|date'
        ]);
        
        $statute = Statute::findOrFail($statuteId);
        
        try {
            $provision = $statute->provisions()->create($validated);
            
            return ApiResponse::success([
                'provision' => $provision
            ], 'Provision created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create provision: ' . $e->getMessage(), 500);
        }
    }
    
    public function show($statuteId, $provisionId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        $provision = $statute->provisions()->with([
            'division:id,division_title',
            'parentProvision:id,provision_title',
            'childProvisions:id,provision_title,provision_number'
        ])->findOrFail($provisionId);
        
        return ApiResponse::success([
            'provision' => $provision
        ], 'Provision retrieved successfully');
    }
    
    public function update(Request $request, $statuteId, $provisionId): JsonResponse
    {
        $validated = $request->validate([
            'provision_type' => 'sometimes|in:section,subsection,paragraph,subparagraph,clause,subclause,item',
            'provision_number' => 'sometimes|string|max:255',
            'provision_title' => 'nullable|string|max:255',
            'provision_text' => 'sometimes|string',
            'marginal_note' => 'nullable|string',
            'interpretation_note' => 'nullable|string',
            'division_id' => 'nullable|exists:statute_divisions,id',
            'parent_provision_id' => 'nullable|exists:statute_provisions,id',
            'sort_order' => 'sometimes|integer|min:0',
            'level' => 'sometimes|integer|min:1|max:10',
            'status' => 'sometimes|in:active,repealed,amended',
            'effective_date' => 'nullable|date'
        ]);
        
        $statute = Statute::findOrFail($statuteId);
        $provision = $statute->provisions()->findOrFail($provisionId);
        
        try {
            $provision->update($validated);
            
            return ApiResponse::success([
                'provision' => $provision
            ], 'Provision updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update provision: ' . $e->getMessage(), 500);
        }
    }
    
    public function destroy($statuteId, $provisionId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        $provision = $statute->provisions()->findOrFail($provisionId);
        
        try {
            $provision->delete();
            
            return ApiResponse::success([], 'Provision deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete provision: ' . $e->getMessage(), 500);
        }
    }
}