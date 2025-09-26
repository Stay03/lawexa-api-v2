<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrendingCollection;
use App\Http\Responses\ApiResponse;
use App\Services\TrendingService;
use App\Services\IpGeolocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class TrendingController extends Controller
{
    public function __construct(
        private TrendingService $trendingService,
        private IpGeolocationService $ipGeolocationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $this->validateAndExtractFilters($request);

            $trendingContent = $this->trendingService->getTrendingContent($filters);

            $response = new TrendingCollection($trendingContent);
            $responseArray = $response->toArray(request());

            // Add filters and stats at the root level
            $responseArray['filters_applied'] = $this->getAppliedFilters($filters);
            $responseArray['stats'] = $this->trendingService->getTrendingStats($filters);

            // Add detected country data if available
            if (isset($filters['detected_country_data'])) {
                $responseArray['detected_country'] = $filters['detected_country_data'];
            }

            // Add country detection status if applicable
            if (isset($filters['country_detection_failed'])) {
                $responseArray['country_detection_status'] = 'failed';
            } elseif (isset($filters['detected_country_data'])) {
                $responseArray['country_detection_status'] = 'success';
            }

            return ApiResponse::success(
                $responseArray,
                'Trending content retrieved successfully'
            );
        } catch (ValidationException $e) {
            return ApiResponse::error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve trending content', ['error' => $e->getMessage()], 500);
        }
    }

    public function cases(Request $request): JsonResponse
    {
        return $this->getContentTypeSpecific($request, 'cases');
    }

    public function statutes(Request $request): JsonResponse
    {
        return $this->getContentTypeSpecific($request, 'statutes');
    }

    public function divisions(Request $request): JsonResponse
    {
        return $this->getContentTypeSpecific($request, 'divisions');
    }

    public function provisions(Request $request): JsonResponse
    {
        return $this->getContentTypeSpecific($request, 'provisions');
    }

    public function notes(Request $request): JsonResponse
    {
        return $this->getContentTypeSpecific($request, 'notes');
    }

    public function folders(Request $request): JsonResponse
    {
        return $this->getContentTypeSpecific($request, 'folders');
    }

    public function comments(Request $request): JsonResponse
    {
        return $this->getContentTypeSpecific($request, 'comments');
    }

    private function getContentTypeSpecific(Request $request, string $type): JsonResponse
    {
        try {
            $filters = $this->validateAndExtractFilters($request);
            $filters['type'] = $type;

            $trendingContent = $this->trendingService->getTrendingContent($filters);

            $message = "Trending {$type} retrieved successfully";

            $response = new TrendingCollection($trendingContent);
            $responseArray = $response->toArray(request());

            // Add metadata at the root level
            $responseArray['content_type'] = $type;
            $responseArray['filters_applied'] = $this->getAppliedFilters($filters);
            $responseArray['stats'] = $this->trendingService->getTrendingStats($filters);

            // Add detected country data if available
            if (isset($filters['detected_country_data'])) {
                $responseArray['detected_country'] = $filters['detected_country_data'];
            }

            // Add country detection status if applicable
            if (isset($filters['country_detection_failed'])) {
                $responseArray['country_detection_status'] = 'failed';
            } elseif (isset($filters['detected_country_data'])) {
                $responseArray['country_detection_status'] = 'success';
            }

            return ApiResponse::success(
                $responseArray,
                $message
            );
        } catch (ValidationException $e) {
            return ApiResponse::error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return ApiResponse::error("Failed to retrieve trending {$type}", ['error' => $e->getMessage()], 500);
        }
    }

    private function validateAndExtractFilters(Request $request): array
    {
        $validated = $request->validate([
            'type' => 'sometimes|string|in:cases,statutes,divisions,provisions,notes,folders,comments,all',
            'country' => 'sometimes|string|max:255',
            'university' => 'sometimes|string|max:255',
            'level' => 'sometimes|string|in:undergraduate,graduate,postgraduate,phd',
            'time_range' => 'sometimes|string|in:today,week,month,year,custom',
            'start_date' => 'sometimes|date|required_if:time_range,custom',
            'end_date' => 'sometimes|date|after_or_equal:start_date|required_if:time_range,custom',
            'per_page' => 'sometimes|integer|min:1|max:50',
            'page' => 'sometimes|integer|min:1',
        ]);

        // Additional validation: level requires university
        if (isset($validated['level']) && !isset($validated['university'])) {
            throw ValidationException::withMessages([
                'level' => 'The level filter requires a university to be specified.'
            ]);
        }

        // Handle country=yes - detect from IP
        if (isset($validated['country']) && strtolower($validated['country']) === 'yes') {
            $detectedCountry = $this->detectCountryFromRequest($request);
            if ($detectedCountry) {
                $validated['country'] = $detectedCountry['name'];
                $validated['detected_country_data'] = $detectedCountry;
            } else {
                // If detection fails, remove country filter
                unset($validated['country']);
                $validated['country_detection_failed'] = true;
            }
        }

        // Set defaults
        $validated['type'] = $validated['type'] ?? 'all';
        $validated['time_range'] = $validated['time_range'] ?? 'week';
        $validated['per_page'] = $validated['per_page'] ?? 15;
        $validated['page'] = $validated['page'] ?? 1;

        return $validated;
    }

    private function getAppliedFilters(array $filters): array
    {
        $applied = [];

        if (($filters['type'] ?? 'all') !== 'all') {
            $applied['content_type'] = $filters['type'];
        }

        if (isset($filters['country'])) {
            if (isset($filters['detected_country_data'])) {
                $applied['country'] = $filters['country'] . ' (detected from IP)';
            } else {
                $applied['country'] = $filters['country'];
            }
        }

        if (isset($filters['university'])) {
            $applied['university'] = $filters['university'];
        }

        if (isset($filters['level'])) {
            $applied['level'] = $filters['level'];
        }

        if (($filters['time_range'] ?? 'week') !== 'week') {
            $applied['time_range'] = $filters['time_range'];

            if ($filters['time_range'] === 'custom') {
                $applied['custom_date_range'] = [
                    'start_date' => $filters['start_date'] ?? null,
                    'end_date' => $filters['end_date'] ?? null,
                ];
            }
        }

        return $applied;
    }

    private function detectCountryFromRequest(Request $request): ?array
    {
        $ipAddress = $request->ip();

        if (!$ipAddress || $ipAddress === '127.0.0.1' || $ipAddress === '::1') {
            return null;
        }

        try {
            $location = $this->ipGeolocationService->getLocation($ipAddress);

            if ($location && isset($location['country']) && !empty($location['country'])) {
                return [
                    'name' => $location['country'],
                    'code' => $location['countryCode'] ?? null,
                    'region' => $location['region'] ?? null,
                    'city' => $location['city'] ?? null,
                    'timezone' => $location['timezone'] ?? null,
                    'ip_address' => $ipAddress,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to detect country from IP in trending endpoint', [
                'ip' => $ipAddress,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    public function stats(Request $request): JsonResponse
    {
        try {
            $filters = $this->validateAndExtractFilters($request);
            
            $stats = $this->trendingService->getTrendingStats($filters);
            
            return ApiResponse::success([
                'stats' => $stats,
                'filters_applied' => $this->getAppliedFilters($filters)
            ], 'Trending statistics retrieved successfully');
        } catch (ValidationException $e) {
            return ApiResponse::error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve trending statistics', ['error' => $e->getMessage()], 500);
        }
    }
}