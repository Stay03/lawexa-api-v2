<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCourtRequest;
use App\Http\Requests\UpdateCourtRequest;
use App\Http\Resources\CourtCollection;
use App\Http\Resources\CourtResource;
use App\Http\Responses\ApiResponse;
use App\Models\Court;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCourtController extends Controller
{
    /**
     * Display a listing of courts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Court::with(['creator:id,name']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('abbreviation', 'like', '%' . $search . '%');
            });
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
     * Store a newly created court.
     */
    public function store(CreateCourtRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        try {
            $court = Court::create($validated);
            $court->load(['creator:id,name']);

            return ApiResponse::success([
                'court' => new CourtResource($court)
            ], 'Court created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create court: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified court.
     */
    public function show(int $id): JsonResponse
    {
        $court = Court::with(['creator:id,name'])->findOrFail($id);

        return ApiResponse::success([
            'court' => new CourtResource($court)
        ], 'Court retrieved successfully');
    }

    /**
     * Update the specified court.
     */
    public function update(UpdateCourtRequest $request, int $id): JsonResponse
    {
        $court = Court::findOrFail($id);
        $validated = $request->validated();

        try {
            $court->update($validated);
            $court->load(['creator:id,name']);

            return ApiResponse::success([
                'court' => new CourtResource($court)
            ], 'Court updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update court: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified court.
     */
    public function destroy(int $id): JsonResponse
    {
        $court = Court::findOrFail($id);

        try {
            $court->delete();

            return ApiResponse::success([], 'Court deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete court: ' . $e->getMessage(), 500);
        }
    }
}
