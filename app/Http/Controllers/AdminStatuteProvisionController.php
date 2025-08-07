<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Models\StatuteProvision;
use App\Models\StatuteDivision;
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
                           ->get();
        
        return ApiResponse::success([
            'provisions' => $provisions
        ], 'Statute provisions retrieved successfully');
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
            'childProvisions:id,parent_provision_id,provision_title,provision_number'
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
    
    public function children(Request $request, $statuteId, $provisionId): JsonResponse
    {
        $statute = Statute::findOrFail($statuteId);
        $parentProvision = $statute->provisions()->findOrFail($provisionId);
        
        // Get child provisions with pagination
        $query = StatuteProvision::where('parent_provision_id', $provisionId)
                                 ->with(['parentProvision:id,provision_title,provision_number']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        if ($request->has('provision_type')) {
            $query->byType($request->provision_type);
        }
        
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        // Get child provisions with pagination
        $childProvisions = $query->orderBy('sort_order')
                                ->paginate($request->get('per_page', 15));
        
        // Build breadcrumb trail
        $breadcrumb = $this->buildProvisionBreadcrumb($parentProvision);
        
        return ApiResponse::success([
            'parent' => [
                'id' => $parentProvision->id,
                'title' => $parentProvision->provision_title,
                'number' => $parentProvision->provision_number,
                'type' => $parentProvision->provision_type,
                'level' => $parentProvision->level,
                'breadcrumb' => $breadcrumb
            ],
            'children' => $childProvisions->items(),
            'meta' => [
                'has_children' => $childProvisions->total() > 0,
                'child_level' => $parentProvision->level + 1,
                'parent_provision_id' => $provisionId,
                'statute_id' => $statuteId,
                'current_page' => $childProvisions->currentPage(),
                'from' => $childProvisions->firstItem(),
                'last_page' => $childProvisions->lastPage(),
                'per_page' => $childProvisions->perPage(),
                'to' => $childProvisions->lastItem(),
                'total' => $childProvisions->total(),
            ],
            'links' => [
                'first' => $childProvisions->url(1),
                'last' => $childProvisions->url($childProvisions->lastPage()),
                'prev' => $childProvisions->previousPageUrl(),
                'next' => $childProvisions->nextPageUrl(),
            ]
        ], 'Provision children retrieved successfully');
    }
    
    private function buildProvisionBreadcrumb(StatuteProvision $provision): array
    {
        $breadcrumb = [];
        
        // Add statute as root
        $breadcrumb[] = [
            'id' => $provision->statute->id,
            'title' => $provision->statute->title,
            'type' => 'statute'
        ];
        
        // Add division
        if ($provision->division) {
            // Build division breadcrumb path
            $divisionPath = $this->buildDivisionPath($provision->division);
            $breadcrumb = array_merge($breadcrumb, $divisionPath);
        }
        
        // Build provision path from root to current
        $current = $provision;
        $provisionPath = [];
        
        while ($current) {
            array_unshift($provisionPath, [
                'id' => $current->id,
                'title' => $current->provision_title,
                'number' => $current->provision_number,
                'type' => $current->provision_type
            ]);
            
            $current = $current->parentProvision;
        }
        
        return array_merge($breadcrumb, $provisionPath);
    }
    
    private function buildDivisionPath(StatuteDivision $division): array
    {
        $path = [];
        $current = $division;
        
        // Build path from current division up to root
        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'title' => $current->division_title,
                'number' => $current->division_number,
                'type' => $current->division_type
            ]);
            
            $current = $current->parentDivision;
        }
        
        return $path;
    }
}