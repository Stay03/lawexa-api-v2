<?php

namespace App\Http\Controllers;

use App\Http\Resources\CaseCollection;
use App\Http\Resources\CaseResource;
use App\Http\Responses\ApiResponse;
use App\Models\CourtCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CourtCase::with(['creator:id,name', 'files']);

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

    public function show(CourtCase $case): JsonResponse
    {
        $case->load(['creator:id,name', 'files']);
        
        return ApiResponse::success([
            'case' => new CaseResource($case)
        ], 'Case retrieved successfully');
    }
}
