<?php

namespace App\Http\Controllers;

use App\Http\Resources\CountryCollection;
use App\Http\Resources\CountryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends Controller
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
     * Display the specified country.
     */
    public function show(Request $request, Country $country): JsonResponse
    {
        $country->load(['creator:id,name']);

        return ApiResponse::success(
            ['country' => new CountryResource($country)],
            'Country retrieved successfully'
        );
    }
}
