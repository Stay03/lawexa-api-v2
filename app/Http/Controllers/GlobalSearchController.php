<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrendingCollection;
use App\Http\Responses\ApiResponse;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class GlobalSearchController extends Controller
{
    public function divisions(Request $request): JsonResponse
    {
        try {
            $filters = $this->validateAndExtractDivisionFilters($request);
            $query = StatuteDivision::query()
                ->with(['statute', 'parentDivision'])
                ->active();

            // Apply search filter
            if (isset($filters['search'])) {
                $query->search($filters['search']);
            }

            // Apply type filter
            if (isset($filters['division_type'])) {
                $query->byType($filters['division_type']);
            }

            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'division_title';
            $sortOrder = $filters['sort_order'] ?? 'asc';

            switch ($sortBy) {
                case 'division_number':
                    $query->orderBy('division_number', $sortOrder);
                    break;
                case 'created_at':
                    $query->orderBy('created_at', $sortOrder);
                    break;
                case 'statute_title':
                    $query->join('statutes', 'statutes.id', '=', 'statute_divisions.statute_id')
                          ->orderBy('statutes.title', $sortOrder);
                    break;
                default:
                    $query->orderBy('division_title', $sortOrder);
            }

            $divisions = $query->paginate($filters['per_page'], ['*'], 'page', $filters['page']);

            $response = new TrendingCollection($divisions);
            $responseArray = $response->toArray(request());

            // Add content type identifier
            $responseArray['content_type'] = 'divisions';
            $responseArray['divisions'] = $responseArray['trending'];
            unset($responseArray['trending']);

            return ApiResponse::success(
                $responseArray,
                'Divisions retrieved successfully'
            );
        } catch (ValidationException $e) {
            return ApiResponse::error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve divisions', ['error' => $e->getMessage()], 500);
        }
    }

    public function provisions(Request $request): JsonResponse
    {
        try {
            $filters = $this->validateAndExtractProvisionFilters($request);
            $query = StatuteProvision::query()
                ->with(['statute', 'division', 'parentProvision'])
                ->active();

            // Apply search filter
            if (isset($filters['search'])) {
                $query->search($filters['search']);
            }

            // Apply type filter
            if (isset($filters['provision_type'])) {
                $query->byType($filters['provision_type']);
            }

            // Apply statute filter
            if (isset($filters['statute_id'])) {
                $query->where('statute_id', $filters['statute_id']);
            }

            // Apply division filter
            if (isset($filters['division_id'])) {
                $query->where('division_id', $filters['division_id']);
            }

            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'provision_number';
            $sortOrder = $filters['sort_order'] ?? 'asc';

            switch ($sortBy) {
                case 'provision_title':
                    $query->orderBy('provision_title', $sortOrder);
                    break;
                case 'created_at':
                    $query->orderBy('created_at', $sortOrder);
                    break;
                case 'statute_title':
                    $query->join('statutes', 'statutes.id', '=', 'statute_provisions.statute_id')
                          ->orderBy('statutes.title', $sortOrder);
                    break;
                default:
                    $query->orderBy('provision_number', $sortOrder);
            }

            $provisions = $query->paginate($filters['per_page'], ['*'], 'page', $filters['page']);

            $response = new TrendingCollection($provisions);
            $responseArray = $response->toArray(request());

            // Add content type identifier
            $responseArray['content_type'] = 'provisions';
            $responseArray['provisions'] = $responseArray['trending'];
            unset($responseArray['trending']);

            return ApiResponse::success(
                $responseArray,
                'Provisions retrieved successfully'
            );
        } catch (ValidationException $e) {
            return ApiResponse::error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve provisions', ['error' => $e->getMessage()], 500);
        }
    }

    private function validateAndExtractDivisionFilters(Request $request): array
    {
        $validated = $request->validate([
            'search' => 'sometimes|string|max:255',
            'division_type' => 'sometimes|string|max:100',
            'sort_by' => 'sometimes|string|in:division_title,division_number,created_at,statute_title',
            'sort_order' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:50',
            'page' => 'sometimes|integer|min:1',
        ]);

        // Set defaults
        $validated['per_page'] = $validated['per_page'] ?? 15;
        $validated['page'] = $validated['page'] ?? 1;
        $validated['sort_order'] = $validated['sort_order'] ?? 'asc';
        $validated['sort_by'] = $validated['sort_by'] ?? 'division_title';

        return $validated;
    }

    private function validateAndExtractProvisionFilters(Request $request): array
    {
        $validated = $request->validate([
            'search' => 'sometimes|string|max:255',
            'provision_type' => 'sometimes|string|max:100',
            'statute_id' => 'sometimes|integer|exists:statutes,id',
            'division_id' => 'sometimes|integer|exists:statute_divisions,id',
            'sort_by' => 'sometimes|string|in:provision_title,provision_number,created_at,statute_title',
            'sort_order' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:50',
            'page' => 'sometimes|integer|min:1',
        ]);

        // Set defaults
        $validated['per_page'] = $validated['per_page'] ?? 15;
        $validated['page'] = $validated['page'] ?? 1;
        $validated['sort_order'] = $validated['sort_order'] ?? 'asc';
        $validated['sort_by'] = $validated['sort_by'] ?? 'provision_number';

        return $validated;
    }
}