<?php

namespace App\Http\Controllers;

use App\Models\ModelView;
use App\Http\Resources\SearchHistoryResource;
use App\Http\Resources\SearchViewResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SearchHistoryController extends Controller
{
    /**
     * Display aggregated search history for current user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // Build base query for search views
            $query = ModelView::fromSearch();

            // Filter by user (authenticated or guest)
            if ($user) {
                $query->where('user_id', $user->id);
            }

            // Apply date filters
            if ($request->has('date_from')) {
                $dateFrom = Carbon::parse($request->date_from)->startOfDay();
                $query->where('viewed_at', '>=', $dateFrom);
            }

            if ($request->has('date_to')) {
                $dateTo = Carbon::parse($request->date_to)->endOfDay();
                $query->where('viewed_at', '<=', $dateTo);
            }

            // Filter by content type
            if ($request->has('content_type')) {
                $contentType = $this->mapContentTypeToClass($request->content_type);
                if ($contentType) {
                    $query->where('viewable_type', $contentType);
                }
            }

            // Filter queries containing search text
            if ($request->has('search')) {
                $query->where('search_query', 'LIKE', '%' . $request->search . '%');
            }

            // Aggregate by search_query
            // Use database-agnostic concatenation (SQLite uses ||, MySQL uses CONCAT)
            $driver = DB::connection()->getDriverName();
            $concatExpression = $driver === 'sqlite'
                ? 'viewable_type || "-" || viewable_id'
                : 'CONCAT(viewable_type, "-", viewable_id)';

            $aggregated = $query->select('search_query')
                ->selectRaw('COUNT(*) as views_count')
                ->selectRaw("COUNT(DISTINCT {$concatExpression}) as unique_content_count")
                ->selectRaw('MIN(viewed_at) as first_searched_at')
                ->selectRaw('MAX(viewed_at) as last_searched_at')
                ->whereNotNull('search_query')
                ->groupBy('search_query');

            // Apply sorting
            $sortBy = $request->input('sort_by', 'last_searched_at');
            $sortOrder = $request->input('sort_order', 'desc');

            // Map sort fields
            $sortColumn = match($sortBy) {
                'first_searched' => 'first_searched_at',
                'last_searched' => 'last_searched_at',
                'views_count' => 'views_count',
                'query' => 'search_query',
                default => 'last_searched_at'
            };

            $aggregated->orderBy($sortColumn, $sortOrder);

            // Paginate
            $perPage = min($request->input('per_page', 15), 50);
            $results = $aggregated->paginate($perPage);

            // Get stats
            $totalStats = ModelView::fromSearch()
                ->when($user, fn($q) => $q->where('user_id', $user->id))
                ->select(DB::raw('COUNT(*) as total_views_from_search'))
                ->selectRaw('COUNT(DISTINCT search_query) as unique_queries')
                ->first();

            return ApiResponse::success([
                'search_history' => SearchHistoryResource::collection($results),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                ],
                'links' => [
                    'first' => $results->url(1),
                    'last' => $results->url($results->lastPage()),
                    'prev' => $results->previousPageUrl(),
                    'next' => $results->nextPageUrl(),
                ],
                'stats' => [
                    'total_searches' => $results->total(),
                    'total_views_from_search' => $totalStats->total_views_from_search ?? 0,
                    'unique_queries' => $totalStats->unique_queries ?? 0,
                ],
            ], 'Search history retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving search history: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving search history', null, 500);
        }
    }

    /**
     * Display individual views initiated from searches.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function views(Request $request)
    {
        try {
            $user = $request->user();

            // Build base query for search views
            $query = ModelView::fromSearch()
                ->with(['viewable', 'user']);

            // Filter by user (authenticated or guest)
            if ($user) {
                $query->where('user_id', $user->id);
            }

            // Filter by specific search query
            if ($request->has('search_query')) {
                $query->where('search_query', $request->search_query);
            }

            // Apply date filters
            if ($request->has('date_from')) {
                $dateFrom = Carbon::parse($request->date_from)->startOfDay();
                $query->where('viewed_at', '>=', $dateFrom);
            }

            if ($request->has('date_to')) {
                $dateTo = Carbon::parse($request->date_to)->endOfDay();
                $query->where('viewed_at', '<=', $dateTo);
            }

            // Filter by content type
            if ($request->has('content_type')) {
                $contentType = $this->mapContentTypeToClass($request->content_type);
                if ($contentType) {
                    $query->where('viewable_type', $contentType);
                }
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'viewed_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $sortColumn = match($sortBy) {
                'viewed_at' => 'viewed_at',
                'query' => 'search_query',
                default => 'viewed_at'
            };

            $query->orderBy($sortColumn, $sortOrder);

            // Paginate
            $perPage = min($request->input('per_page', 15), 50);
            $views = $query->paginate($perPage);

            return ApiResponse::success([
                'search_views' => SearchViewResource::collection($views),
                'meta' => [
                    'current_page' => $views->currentPage(),
                    'last_page' => $views->lastPage(),
                    'per_page' => $views->perPage(),
                    'total' => $views->total(),
                    'from' => $views->firstItem(),
                    'to' => $views->lastItem(),
                ],
                'links' => [
                    'first' => $views->url(1),
                    'last' => $views->url($views->lastPage()),
                    'prev' => $views->previousPageUrl(),
                    'next' => $views->nextPageUrl(),
                ],
                'filters' => [
                    'search_query' => $request->search_query,
                    'content_type' => $request->content_type,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                ],
            ], 'Search views retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving search views: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving search views', null, 500);
        }
    }

    /**
     * Get overall search statistics for current user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        try {
            $user = $request->user();

            // Build base query
            $query = ModelView::fromSearch();

            if ($user) {
                $query->where('user_id', $user->id);
            }

            // Apply date filters
            if ($request->has('date_from')) {
                $dateFrom = Carbon::parse($request->date_from)->startOfDay();
                $query->where('viewed_at', '>=', $dateFrom);
            } else {
                $dateFrom = null;
            }

            if ($request->has('date_to')) {
                $dateTo = Carbon::parse($request->date_to)->endOfDay();
                $query->where('viewed_at', '<=', $dateTo);
            } else {
                $dateTo = null;
            }

            // Get overall stats
            $totalSearches = $query->distinct('search_query')->count('search_query');
            $totalViewsFromSearch = $query->count();
            $uniqueQueries = $query->distinct('search_query')->count('search_query');

            $viewsPerSearchAvg = $totalSearches > 0 ? round($totalViewsFromSearch / $totalSearches, 2) : 0;

            // Get most searched query
            $mostSearchedQuery = ModelView::fromSearch()
                ->when($user, fn($q) => $q->where('user_id', $user->id))
                ->when($request->has('date_from'), fn($q) => $q->where('viewed_at', '>=', Carbon::parse($request->date_from)->startOfDay()))
                ->when($request->has('date_to'), fn($q) => $q->where('viewed_at', '<=', Carbon::parse($request->date_to)->endOfDay()))
                ->select('search_query')
                ->selectRaw('COUNT(*) as count')
                ->whereNotNull('search_query')
                ->groupBy('search_query')
                ->orderByDesc('count')
                ->first();

            // Get most viewed content from search
            $mostViewedFromSearch = ModelView::fromSearch()
                ->when($user, fn($q) => $q->where('user_id', $user->id))
                ->when($request->has('date_from'), fn($q) => $q->where('viewed_at', '>=', Carbon::parse($request->date_from)->startOfDay()))
                ->when($request->has('date_to'), fn($q) => $q->where('viewed_at', '<=', Carbon::parse($request->date_to)->endOfDay()))
                ->select('viewable_type', 'viewable_id')
                ->selectRaw('COUNT(*) as views')
                ->groupBy('viewable_type', 'viewable_id')
                ->orderByDesc('views')
                ->with('viewable')
                ->first();

            // Get content type breakdown
            $contentTypeBreakdown = ModelView::fromSearch()
                ->when($user, fn($q) => $q->where('user_id', $user->id))
                ->when($request->has('date_from'), fn($q) => $q->where('viewed_at', '>=', Carbon::parse($request->date_from)->startOfDay()))
                ->when($request->has('date_to'), fn($q) => $q->where('viewed_at', '<=', Carbon::parse($request->date_to)->endOfDay()))
                ->select('viewable_type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('viewable_type')
                ->get()
                ->mapWithKeys(function ($item) {
                    $type = $this->mapClassToContentType($item->viewable_type);
                    return [$type => $item->count];
                });

            // Calculate period
            $firstView = ModelView::fromSearch()
                ->when($user, fn($q) => $q->where('user_id', $user->id))
                ->orderBy('viewed_at', 'asc')
                ->first();

            $lastView = ModelView::fromSearch()
                ->when($user, fn($q) => $q->where('user_id', $user->id))
                ->orderBy('viewed_at', 'desc')
                ->first();

            $periodFrom = $dateFrom ?? ($firstView ? $firstView->viewed_at->format('Y-m-d') : null);
            $periodTo = $dateTo ?? ($lastView ? $lastView->viewed_at->format('Y-m-d') : null);

            $days = $periodFrom && $periodTo ?
                Carbon::parse($periodFrom)->diffInDays(Carbon::parse($periodTo)) + 1 : 0;

            return ApiResponse::success([
                'total_searches' => $totalSearches,
                'total_views_from_search' => $totalViewsFromSearch,
                'unique_queries' => $uniqueQueries,
                'views_per_search_avg' => $viewsPerSearchAvg,
                'most_searched_query' => $mostSearchedQuery?->search_query,
                'most_viewed_from_search' => $mostViewedFromSearch ? [
                    'type' => $this->mapClassToContentType($mostViewedFromSearch->viewable_type),
                    'id' => $mostViewedFromSearch->viewable_id,
                    'title' => $mostViewedFromSearch->viewable?->title ?? $mostViewedFromSearch->viewable?->case_name ?? 'Unknown',
                    'views' => $mostViewedFromSearch->views,
                ] : null,
                'content_type_breakdown' => $contentTypeBreakdown,
                'period' => [
                    'from' => $periodFrom,
                    'to' => $periodTo,
                    'days' => $days,
                ],
            ], 'Search statistics retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving search stats: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving search statistics', null, 500);
        }
    }

    /**
     * Map content type string to full class name.
     *
     * @param string $contentType
     * @return string|null
     */
    private function mapContentTypeToClass(string $contentType): ?string
    {
        return match(strtolower($contentType)) {
            'case' => 'App\\Models\\CourtCase',
            'statute' => 'App\\Models\\Statute',
            'division' => 'App\\Models\\Division',
            'provision' => 'App\\Models\\Provision',
            'schedule' => 'App\\Models\\Schedule',
            'note' => 'App\\Models\\Note',
            'folder' => 'App\\Models\\Folder',
            'comment' => 'App\\Models\\Comment',
            default => null,
        };
    }

    /**
     * Map full class name to content type string.
     *
     * @param string $className
     * @return string
     */
    private function mapClassToContentType(string $className): string
    {
        return match($className) {
            'App\\Models\\CourtCase' => 'case',
            'App\\Models\\Statute' => 'statute',
            'App\\Models\\Division' => 'division',
            'App\\Models\\Provision' => 'provision',
            'App\\Models\\Schedule' => 'schedule',
            'App\\Models\\Note' => 'note',
            'App\\Models\\Folder' => 'folder',
            'App\\Models\\Comment' => 'comment',
            default => 'unknown',
        };
    }
}
