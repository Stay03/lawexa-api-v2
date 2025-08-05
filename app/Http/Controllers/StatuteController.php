<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Http\Resources\StatuteResource;
use App\Http\Resources\StatuteCollection;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StatuteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        $query = Statute::with(['creator:id,name'])
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
        $divisions = $statute->divisions()
                            ->with(['parentDivision:id,division_title', 'childDivisions:id,division_title,parent_division_id'])
                            ->paginate($request->get('per_page', 50));
        
        return ApiResponse::success(
            $divisions,
            'Statute divisions retrieved successfully'
        );
    }
    
    public function provisions(Request $request, Statute $statute): JsonResponse
    {
        $provisions = $statute->provisions()
                             ->with(['division:id,division_title', 'parentProvision:id,provision_title'])
                             ->paginate($request->get('per_page', 100));
        
        return ApiResponse::success(
            $provisions,
            'Statute provisions retrieved successfully'
        );
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

    public function showDivision(Request $request, Statute $statute, $divisionSlug): JsonResponse
    {
        $division = $statute->divisions()->where('slug', $divisionSlug)->firstOrFail();
        
        $division->load([
            'parentDivision:id,division_title',
            'childDivisions:id,division_title,division_number',
            'provisions:id,provision_title,provision_number'
        ]);
        
        return ApiResponse::success([
            'division' => $division
        ], 'Statute division retrieved successfully');
    }

    public function showProvision(Request $request, Statute $statute, $provisionSlug): JsonResponse
    {
        $provision = $statute->provisions()->where('slug', $provisionSlug)->firstOrFail();
        
        $provision->load([
            'division:id,division_title',
            'parentProvision:id,provision_title',
            'childProvisions:id,provision_title,provision_number'
        ]);
        
        return ApiResponse::success([
            'provision' => $provision
        ], 'Statute provision retrieved successfully');
    }

    public function showSchedule(Request $request, Statute $statute, $scheduleSlug): JsonResponse
    {
        $schedule = $statute->schedules()->where('slug', $scheduleSlug)->firstOrFail();
        
        return ApiResponse::success([
            'schedule' => $schedule
        ], 'Statute schedule retrieved successfully');
    }
}