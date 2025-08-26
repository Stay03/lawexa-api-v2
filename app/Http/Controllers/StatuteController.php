<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use App\Models\StatuteSchedule;
use App\Http\Resources\StatuteResource;
use App\Http\Resources\StatuteCollection;
use App\Http\Resources\StatuteDivisionCollection;
use App\Http\Responses\ApiResponse;
use App\Services\ViewTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StatuteController extends Controller
{
    public function __construct(
        private ViewTrackingService $viewTrackingService
    ) {}
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        $query = Statute::with(['creator:id,name'])
                        ->withViewsCount()
                        ->active();
        
        // Apply filters
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
        
        // Sorting
        $sortBy = $request->get('sort_by', 'year_enacted');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $statutes = $query->paginate($perPage);
        
        return ApiResponse::success(
            new StatuteCollection($statutes),
            'Statutes retrieved successfully'
        );
    }
    
    public function show(Request $request, Statute $statute): JsonResponse
    {
        $includeRelated = $request->boolean('include_related', false);
        $includeAmendments = $request->boolean('include_amendments', false);
        $includeCitations = $request->boolean('include_citations', false);
        
        $with = ['creator:id,name', 'files'];
        
        if ($includeRelated) {
            $with = array_merge($with, [
                'parentStatute:id,title,slug',
                'childStatutes:id,title,slug',
                'repealingStatute:id,title,slug'
            ]);
        }
        
        if ($includeAmendments) {
            $with[] = 'amendments:id,title,slug';
            $with[] = 'amendedBy:id,title,slug';
        }
        
        if ($includeCitations) {
            $with[] = 'citedStatutes:id,title,slug';
            $with[] = 'citingStatutes:id,title,slug';
        }
        
        $statute->load($with);
        
        return ApiResponse::success([
            'statute' => new StatuteResource($statute)
        ], 'Statute retrieved successfully');
    }
    
    public function divisions(Request $request, Statute $statute): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        $query = $statute->divisions()
                         ->topLevel()
                         ->active()
                         ->withViewsCount()
                         ->with(['parentDivision:id,division_title,slug']);
        
        // Apply filters
        if ($request->has('division_type')) {
            $query->byType($request->division_type);
        }
        
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('division_title', 'like', '%' . $request->search . '%')
                  ->orWhere('division_number', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }
        
        $divisions = $query->orderBy('sort_order')
                          ->paginate($perPage);
        
        // Build breadcrumb for statute context
        $breadcrumb = [
            [
                'id' => $statute->id,
                'title' => $statute->title,
                'slug' => $statute->slug,
                'type' => 'statute'
            ]
        ];
        
        return ApiResponse::success([
            'statute' => [
                'id' => $statute->id,
                'title' => $statute->title,
                'slug' => $statute->slug,
                'breadcrumb' => $breadcrumb
            ],
            'divisions' => $divisions->items(),
            'meta' => [
                'statute_slug' => $statute->slug,
                'current_page' => $divisions->currentPage(),
                'from' => $divisions->firstItem(),
                'last_page' => $divisions->lastPage(),
                'per_page' => $divisions->perPage(),
                'to' => $divisions->lastItem(),
                'total' => $divisions->total(),
            ],
            'links' => [
                'first' => $divisions->url(1),
                'last' => $divisions->url($divisions->lastPage()),
                'prev' => $divisions->previousPageUrl(),
                'next' => $divisions->nextPageUrl(),
            ]
        ], 'Statute divisions retrieved successfully');
    }
    
    public function provisions(Request $request, Statute $statute): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        $query = $statute->provisions()
                         ->active()
                         ->withViewsCount()
                         ->with(['division:id,division_title,slug', 'parentProvision:id,provision_title,slug']);
        
        // Apply filters
        if ($request->has('provision_type')) {
            $query->byType($request->provision_type);
        }
        
        if ($request->has('division_slug') && $request->division_slug) {
            $division = $statute->divisions()->where('slug', $request->division_slug)->first();
            if ($division) {
                $query->where('division_id', $division->id);
            }
        }
        
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        $provisions = $query->orderBy('sort_order')
                           ->paginate($perPage);
        
        // Build breadcrumb for statute context
        $breadcrumb = [
            [
                'id' => $statute->id,
                'title' => $statute->title,
                'slug' => $statute->slug,
                'type' => 'statute'
            ]
        ];
        
        return ApiResponse::success([
            'statute' => [
                'id' => $statute->id,
                'title' => $statute->title,
                'slug' => $statute->slug,
                'breadcrumb' => $breadcrumb
            ],
            'provisions' => $provisions->items(),
            'meta' => [
                'statute_slug' => $statute->slug,
                'current_page' => $provisions->currentPage(),
                'from' => $provisions->firstItem(),
                'last_page' => $provisions->lastPage(),
                'per_page' => $provisions->perPage(),
                'to' => $provisions->lastItem(),
                'total' => $provisions->total(),
            ],
            'links' => [
                'first' => $provisions->url(1),
                'last' => $provisions->url($provisions->lastPage()),
                'prev' => $provisions->previousPageUrl(),
                'next' => $provisions->nextPageUrl(),
            ]
        ], 'Statute provisions retrieved successfully');
    }
    
    public function schedules(Request $request, Statute $statute): JsonResponse
    {
        $schedules = $statute->schedules()
                            ->paginate($request->get('per_page', 20));
        
        return ApiResponse::success(
            $schedules,
            'Statute schedules retrieved successfully'
        );
    }

    public function showDivision(Request $request, Statute $statute, StatuteDivision $division): JsonResponse
    {
        $division->load([
            'parentDivision:id,division_title',
            'childDivisions:id,division_title,division_number',
            'provisions:id,provision_title,provision_number'
        ]);
        
        return ApiResponse::success([
            'division' => $division
        ], 'Statute division retrieved successfully');
    }

    public function showProvision(Request $request, Statute $statute, StatuteProvision $provision): JsonResponse
    {
        $provision->load([
            'division:id,division_title',
            'parentProvision:id,provision_title',
            'childProvisions:id,provision_title,provision_number'
        ]);
        
        return ApiResponse::success([
            'provision' => $provision
        ], 'Statute provision retrieved successfully');
    }

    public function showSchedule(Request $request, Statute $statute, StatuteSchedule $schedule): JsonResponse
    {
        return ApiResponse::success([
            'schedule' => $schedule
        ], 'Statute schedule retrieved successfully');
    }
    
    public function divisionChildren(Request $request, Statute $statute, StatuteDivision $division): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        // Get child divisions
        $query = StatuteDivision::where('parent_division_id', $division->id)
                                ->active()
                                ->with(['parentDivision:id,division_title,division_number,slug']);
        
        // Apply filters
        if ($request->has('division_type')) {
            $query->byType($request->division_type);
        }
        
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('division_title', 'like', '%' . $request->search . '%')
                  ->orWhere('division_number', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }
        
        $children = $query->orderBy('sort_order')
                         ->paginate($perPage);
        
        // Build breadcrumb trail
        $breadcrumb = $this->buildDivisionBreadcrumb($division, $statute);
        
        return ApiResponse::success([
            'parent' => [
                'id' => $division->id,
                'title' => $division->division_title,
                'number' => $division->division_number,
                'slug' => $division->slug,
                'type' => $division->division_type,
                'level' => $division->level,
                'breadcrumb' => $breadcrumb
            ],
            'children' => $children->items(),
            'meta' => [
                'has_children' => $children->total() > 0,
                'child_level' => $division->level + 1,
                'parent_division_slug' => $division->slug,
                'statute_slug' => $statute->slug,
                'current_page' => $children->currentPage(),
                'from' => $children->firstItem(),
                'last_page' => $children->lastPage(),
                'per_page' => $children->perPage(),
                'to' => $children->lastItem(),
                'total' => $children->total(),
            ],
            'links' => [
                'first' => $children->url(1),
                'last' => $children->url($children->lastPage()),
                'prev' => $children->previousPageUrl(),
                'next' => $children->nextPageUrl(),
            ]
        ], 'Division children retrieved successfully');
    }
    
    public function divisionProvisions(Request $request, Statute $statute, StatuteDivision $division): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        // Get provisions for this division
        $query = StatuteProvision::where('division_id', $division->id)
                                 ->active()
                                 ->with(['parentProvision:id,provision_title,provision_number,slug']);
        
        // Apply filters
        if ($request->has('provision_type')) {
            $query->byType($request->provision_type);
        }
        
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        $provisions = $query->orderBy('sort_order')
                           ->paginate($perPage);
        
        // Build breadcrumb trail
        $breadcrumb = $this->buildDivisionBreadcrumb($division, $statute);
        
        return ApiResponse::success([
            'division' => [
                'id' => $division->id,
                'title' => $division->division_title,
                'number' => $division->division_number,
                'slug' => $division->slug,
                'type' => $division->division_type,
                'level' => $division->level,
                'breadcrumb' => $breadcrumb
            ],
            'provisions' => $provisions->items(),
            'meta' => [
                'division_slug' => $division->slug,
                'statute_slug' => $statute->slug,
                'current_page' => $provisions->currentPage(),
                'from' => $provisions->firstItem(),
                'last_page' => $provisions->lastPage(),
                'per_page' => $provisions->perPage(),
                'to' => $provisions->lastItem(),
                'total' => $provisions->total(),
            ],
            'links' => [
                'first' => $provisions->url(1),
                'last' => $provisions->url($provisions->lastPage()),
                'prev' => $provisions->previousPageUrl(),
                'next' => $provisions->nextPageUrl(),
            ]
        ], 'Division provisions retrieved successfully');
    }
    
    public function provisionChildren(Request $request, Statute $statute, StatuteProvision $provision): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        // Get child provisions
        $query = StatuteProvision::where('parent_provision_id', $provision->id)
                                 ->active()
                                 ->with(['parentProvision:id,provision_title,provision_number,slug']);
        
        // Apply filters
        if ($request->has('provision_type')) {
            $query->byType($request->provision_type);
        }
        
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        $children = $query->orderBy('sort_order')
                         ->paginate($perPage);
        
        // Build breadcrumb trail
        $breadcrumb = $this->buildProvisionBreadcrumb($provision, $statute);
        
        return ApiResponse::success([
            'parent' => [
                'id' => $provision->id,
                'title' => $provision->provision_title,
                'number' => $provision->provision_number,
                'slug' => $provision->slug,
                'type' => $provision->provision_type,
                'level' => $provision->level,
                'breadcrumb' => $breadcrumb
            ],
            'children' => $children->items(),
            'meta' => [
                'has_children' => $children->total() > 0,
                'child_level' => $provision->level + 1,
                'parent_provision_slug' => $provision->slug,
                'statute_slug' => $statute->slug,
                'current_page' => $children->currentPage(),
                'from' => $children->firstItem(),
                'last_page' => $children->lastPage(),
                'per_page' => $children->perPage(),
                'to' => $children->lastItem(),
                'total' => $children->total(),
            ],
            'links' => [
                'first' => $children->url(1),
                'last' => $children->url($children->lastPage()),
                'prev' => $children->previousPageUrl(),
                'next' => $children->nextPageUrl(),
            ]
        ], 'Provision children retrieved successfully');
    }
    
    private function buildDivisionBreadcrumb(StatuteDivision $division, Statute $statute): array
    {
        $breadcrumb = [];
        $current = $division;
        
        // Build breadcrumb from current division up to root
        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'title' => $current->division_title,
                'number' => $current->division_number,
                'slug' => $current->slug,
                'type' => $current->division_type
            ]);
            
            $current = $current->parentDivision;
        }
        
        // Add statute as root
        array_unshift($breadcrumb, [
            'id' => $statute->id,
            'title' => $statute->title,
            'slug' => $statute->slug,
            'type' => 'statute'
        ]);
        
        return $breadcrumb;
    }
    
    private function buildProvisionBreadcrumb(StatuteProvision $provision, Statute $statute): array
    {
        $breadcrumb = [];
        
        // Add statute as root
        $breadcrumb[] = [
            'id' => $statute->id,
            'title' => $statute->title,
            'slug' => $statute->slug,
            'type' => 'statute'
        ];
        
        // Add division if exists
        if ($provision->division) {
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
                'slug' => $current->slug,
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
                'slug' => $current->slug,
                'type' => $current->division_type
            ]);
            
            $current = $current->parentDivision;
        }
        
        return $path;
    }
}