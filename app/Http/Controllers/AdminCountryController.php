<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCountryRequest;
use App\Http\Requests\UpdateCountryRequest;
use App\Http\Resources\CountryCollection;
use App\Http\Resources\CountryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCountryController extends Controller
{
    /**
     * Display a listing of countries.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Country::with(['creator:id,name']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('abbreviation', 'like', '%' . $search . '%');
            });
        }

        $countries = $query->latest()
                       ->paginate($request->get('per_page', 15));

        $countryCollection = new CountryCollection($countries);

        return ApiResponse::success(
            $countryCollection->toArray($request),
            'Countries retrieved successfully'
        );
    }

    /**
     * Store a newly created country.
     */
    public function store(CreateCountryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        try {
            $country = Country::create($validated);
            $country->load(['creator:id,name']);

            return ApiResponse::success([
                'country' => new CountryResource($country)
            ], 'Country created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create country: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified country.
     */
    public function show(int $id): JsonResponse
    {
        $country = Country::with(['creator:id,name'])->findOrFail($id);

        return ApiResponse::success([
            'country' => new CountryResource($country)
        ], 'Country retrieved successfully');
    }

    /**
     * Update the specified country.
     */
    public function update(UpdateCountryRequest $request, int $id): JsonResponse
    {
        $country = Country::findOrFail($id);
        $validated = $request->validated();

        try {
            $country->update($validated);
            $country->load(['creator:id,name']);

            return ApiResponse::success([
                'country' => new CountryResource($country)
            ], 'Country updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update country: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified country.
     */
    public function destroy(int $id): JsonResponse
    {
        $country = Country::findOrFail($id);

        try {
            $country->delete();

            return ApiResponse::success([], 'Country deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete country: ' . $e->getMessage(), 500);
        }
    }
}
