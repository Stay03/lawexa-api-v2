<?php

namespace App\Http\Controllers;

use App\Http\Resources\CaseCollection;
use App\Http\Resources\CaseResource;
use App\Http\Responses\ApiResponse;
use App\Models\CourtCase;
use App\Services\ViewTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    public function __construct(
        private ViewTrackingService $viewTrackingService
    ) {}
    public function index(Request $request): JsonResponse
    {
        $includeSimilarCases = $request->boolean('include_similar_cases', false);
        $includeCitedCases = $request->boolean('include_cited_cases', false);
        
        $with = ['creator:id,name', 'files'];
        if ($includeSimilarCases) {
            $with[] = 'similarCases:id,title,slug,court,date,country,citation';
            $with[] = 'casesWhereThisIsSimilar:id,title,slug,court,date,country,citation';
        }
        if ($includeCitedCases) {
            $with[] = 'citedCases:id,title,slug,court,date,country,citation';
            $with[] = 'casesThatCiteThis:id,title,slug,court,date,country,citation';
        }
        
        $query = CourtCase::with($with)->withViewsCount();

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

        $cases = $query->latest()
                      ->paginate($request->get('per_page', 15));

        $caseCollection = new CaseCollection($cases);
        
        return ApiResponse::success(
            $caseCollection->toArray($request),
            'Cases retrieved successfully'
        );
    }

    public function show(Request $request, CourtCase $case): JsonResponse
    {
        $case->load([
            'creator:id,name', 
            'files',
            'caseReport',
            'similarCases:id,title,slug,court,date,country,citation',
            'casesWhereThisIsSimilar:id,title,slug,court,date,country,citation',
            'citedCases:id,title,slug,court,date,country,citation',
            'casesThatCiteThis:id,title,slug,court,date,country,citation'
        ]);
        
        return ApiResponse::success([
            'case' => new CaseResource($case)
        ], 'Case retrieved successfully');
    }
}
