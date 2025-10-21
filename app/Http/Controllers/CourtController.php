<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourtCollection;
use App\Http\Resources\CourtResource;
use App\Http\Responses\ApiResponse;
use App\Models\Court;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    /**
     * Display a listing of courts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Court::with(['creator:id,name']);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $courts = $query->latest()
                       ->paginate($request->get('per_page', 15));

        $courtCollection = new CourtCollection($courts);

        return ApiResponse::success(
            $courtCollection->toArray($request),
            'Courts retrieved successfully'
        );
    }

    /**
     * Display the specified court.
     */
    public function show(Request $request, Court $court): JsonResponse
    {
        $court->load(['creator:id,name']);

        return ApiResponse::success(
            ['court' => new CourtResource($court)],
            'Court retrieved successfully'
        );
    }
}
