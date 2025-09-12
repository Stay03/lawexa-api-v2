<?php

namespace App\Http\Controllers;

use App\Http\Resources\ModelViewCollection;
use App\Models\ModelView;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ViewStatsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin', 'researcher'])) {
            return $this->forbiddenResponse('Admin access required');
        }

        $validated = $request->validate([
            'user_id' => 'sometimes|integer|exists:users,id',
            'model_type' => 'sometimes|string',
            'time_filter' => 'sometimes|string|in:today,this_week,this_month,last_24h,last_7d,last_30d,custom',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'country' => 'sometimes|string',
            'ip_address' => 'sometimes|ip',
            'search' => 'sometimes|string|max:255',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'is_bot' => 'sometimes|boolean',
            'bot_name' => 'sometimes|string|max:255',
            'is_search_engine' => 'sometimes|boolean',
            'is_social_media' => 'sometimes|boolean',
            'sort_by' => 'sometimes|string|in:bot_status,viewed_at,bot_name',
        ]);

        $query = ModelView::with(['user:id,name,email,role', 'viewable']);

        // Apply time filter (takes precedence over individual date filters)
        if (isset($validated['time_filter'])) {
            $timeRange = $this->getTimeRange(
                $validated['time_filter'],
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null
            );
            $query->whereBetween('viewed_at', [$timeRange['start'], $timeRange['end']]);
        } else {
            // Apply individual date filters if no time_filter is provided
            if (isset($validated['start_date'])) {
                $startDate = Carbon::parse($validated['start_date']);
                $query->whereDate('viewed_at', '>=', $startDate);
            }

            if (isset($validated['end_date'])) {
                $endDate = Carbon::parse($validated['end_date']);
                $query->whereDate('viewed_at', '<=', $endDate);
            }
        }

        // Apply other filters
        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (isset($validated['model_type'])) {
            $query->where('viewable_type', $validated['model_type']);
        }

        if (isset($validated['country'])) {
            $query->where('ip_country', 'like', '%' . $validated['country'] . '%');
        }

        if (isset($validated['ip_address'])) {
            $query->where('ip_address', $validated['ip_address']);
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('user_agent', 'like', '%' . $search . '%')
                  ->orWhere('ip_country', 'like', '%' . $search . '%')
                  ->orWhere('ip_city', 'like', '%' . $search . '%')
                  ->orWhere('device_type', 'like', '%' . $search . '%')
                  ->orWhere('device_platform', 'like', '%' . $search . '%')
                  ->orWhere('device_browser', 'like', '%' . $search . '%')
                  ->orWhere('bot_name', 'like', '%' . $search . '%');
            });
        }

        // Apply bot filtering
        if (isset($validated['is_bot'])) {
            if ($validated['is_bot']) {
                $query->botViews();
            } else {
                $query->humanViews();
            }
        }

        if (isset($validated['bot_name'])) {
            $query->byBotName($validated['bot_name']);
        }

        if (isset($validated['is_search_engine']) && $validated['is_search_engine']) {
            $query->searchEngineViews();
        }

        if (isset($validated['is_social_media']) && $validated['is_social_media']) {
            $query->socialMediaViews();
        }

        // Apply sorting
        $sortBy = $validated['sort_by'] ?? 'viewed_at';
        switch ($sortBy) {
            case 'bot_status':
                $query->orderBy('is_bot', 'desc')->orderBy('viewed_at', 'desc');
                break;
            case 'bot_name':
                $query->orderBy('bot_name', 'asc')->orderBy('viewed_at', 'desc');
                break;
            default:
                $query->latest('viewed_at');
        }

        $perPage = $validated['per_page'] ?? 15;
        $views = $query->paginate($perPage);

        return $this->successResponse(
            new ModelViewCollection($views),
            'Views retrieved successfully'
        );
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin', 'researcher'])) {
            return $this->forbiddenResponse('Admin access required');
        }

        $validated = $request->validate([
            'time_filter' => 'sometimes|string|in:today,this_week,this_month,last_24h,last_7d,last_30d,custom',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'model_type' => 'sometimes|string',
            'country' => 'sometimes|string',
            'is_bot' => 'sometimes|boolean',
            'bot_name' => 'sometimes|string|max:255',
            'is_search_engine' => 'sometimes|boolean',
            'is_social_media' => 'sometimes|boolean',
        ]);

        // Determine time periods
        $timeFilter = $validated['time_filter'] ?? 'last_7d';
        $periods = $this->calculateTimePeriods($timeFilter, $validated['start_date'] ?? null, $validated['end_date'] ?? null);
        
        $currentPeriod = $periods['current'];
        $comparisonPeriod = $periods['comparison'];

        // Build base query with filters
        $baseQuery = ModelView::query();
        
        if (isset($validated['model_type'])) {
            $baseQuery->where('viewable_type', $validated['model_type']);
        }
        
        if (isset($validated['country'])) {
            $baseQuery->where('ip_country', 'like', '%' . $validated['country'] . '%');
        }

        // Apply bot filtering to base query
        if (isset($validated['is_bot'])) {
            if ($validated['is_bot']) {
                $baseQuery->where('is_bot', true);
            } else {
                $baseQuery->where(function($q) {
                    $q->where('is_bot', false)->orWhereNull('is_bot');
                });
            }
        }

        if (isset($validated['bot_name'])) {
            $baseQuery->where('bot_name', $validated['bot_name']);
        }

        if (isset($validated['is_search_engine']) && $validated['is_search_engine']) {
            $baseQuery->where('is_search_engine', true);
        }

        if (isset($validated['is_social_media']) && $validated['is_social_media']) {
            $baseQuery->where('is_social_media', true);
        }

        // Calculate current period metrics
        $currentMetrics = $this->calculatePeriodMetrics($baseQuery, $currentPeriod['start'], $currentPeriod['end']);
        
        // Calculate comparison period metrics
        $comparisonMetrics = $this->calculatePeriodMetrics($baseQuery, $comparisonPeriod['start'], $comparisonPeriod['end']);

        // Calculate percentage changes
        $metrics = [
            'total_views' => [
                'current' => $currentMetrics['total_views'],
                'previous' => $comparisonMetrics['total_views'],
                'change_percent' => $this->calculatePercentageChange($comparisonMetrics['total_views'], $currentMetrics['total_views']),
            ],
            'unique_users' => [
                'current' => $currentMetrics['unique_users'],
                'previous' => $comparisonMetrics['unique_users'],
                'change_percent' => $this->calculatePercentageChange($comparisonMetrics['unique_users'], $currentMetrics['unique_users']),
            ],
            'guest_views' => [
                'current' => $currentMetrics['guest_views'],
                'previous' => $comparisonMetrics['guest_views'],
                'change_percent' => $this->calculatePercentageChange($comparisonMetrics['guest_views'], $currentMetrics['guest_views']),
            ],
            'registered_views' => [
                'current' => $currentMetrics['registered_views'],
                'previous' => $comparisonMetrics['registered_views'],
                'change_percent' => $this->calculatePercentageChange($comparisonMetrics['registered_views'], $currentMetrics['registered_views']),
            ],
            'bot_views' => [
                'current' => $currentMetrics['bot_views'],
                'previous' => $comparisonMetrics['bot_views'],
                'change_percent' => $this->calculatePercentageChange($comparisonMetrics['bot_views'], $currentMetrics['bot_views']),
            ],
            'human_views' => [
                'current' => $currentMetrics['human_views'],
                'previous' => $comparisonMetrics['human_views'],
                'change_percent' => $this->calculatePercentageChange($comparisonMetrics['human_views'], $currentMetrics['human_views']),
            ],
            'search_engine_views' => [
                'current' => $currentMetrics['search_engine_views'],
                'previous' => $comparisonMetrics['search_engine_views'],
                'change_percent' => $this->calculatePercentageChange($comparisonMetrics['search_engine_views'], $currentMetrics['search_engine_views']),
            ],
            'social_media_views' => [
                'current' => $currentMetrics['social_media_views'],
                'previous' => $comparisonMetrics['social_media_views'],
                'change_percent' => $this->calculatePercentageChange($comparisonMetrics['social_media_views'], $currentMetrics['social_media_views']),
            ],
        ];

        // Get additional analytics for current period
        $analytics = $this->getDetailedAnalytics($baseQuery, $currentPeriod['start'], $currentPeriod['end']);

        return $this->successResponse([
            'time_filter' => [
                'type' => $timeFilter,
                'current_period' => [
                    'label' => $periods['labels']['current'],
                    'start' => $currentPeriod['start']->toDateString(),
                    'end' => $currentPeriod['end']->toDateString(),
                ],
                'comparison_period' => [
                    'label' => $periods['labels']['comparison'],
                    'start' => $comparisonPeriod['start']->toDateString(),
                    'end' => $comparisonPeriod['end']->toDateString(),
                ],
            ],
            'metrics' => $metrics,
            'analytics' => $analytics,
        ], 'Dashboard data retrieved successfully');
    }

    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin', 'researcher'])) {
            return $this->forbiddenResponse('Admin access required');
        }

        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'model_type' => 'sometimes|string',
        ]);

        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : Carbon::now()->subDays(30);
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : Carbon::now();
        
        $query = ModelView::whereBetween('viewed_at', [$startDate, $endDate]);
        
        if (isset($validated['model_type'])) {
            $query->where('viewable_type', $validated['model_type']);
        }

        $stats = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'totals' => [
                'total_views' => $query->count(),
                'unique_users' => $query->distinct('user_id')->whereNotNull('user_id')->count(),
                'unique_ips' => $query->distinct('ip_address')->count(),
                'guest_views' => $query->whereHas('user', function($q) {
                    $q->where('role', 'guest');
                })->count(),
                'authenticated_views' => $query->whereHas('user', function($q) {
                    $q->where('role', '!=', 'guest');
                })->count(),
            ],
            'daily_breakdown' => $this->getDailyBreakdown($startDate, $endDate, $validated['model_type'] ?? null),
            'top_models' => $this->getTopModels($startDate, $endDate, 10),
            'recent_activity' => ModelView::with(['viewable', 'user:id,name,role'])
                ->whereBetween('viewed_at', [$startDate, $endDate])
                ->latest('viewed_at')
                ->limit(20)
                ->get()
                ->map(function($view) {
                    return [
                        'id' => $view->id,
                        'model_type' => class_basename($view->viewable_type),
                        'model_id' => $view->viewable_id,
                        'user' => $view->user ? [
                            'id' => $view->user->id,
                            'name' => $view->user->name,
                            'role' => $view->user->role,
                        ] : null,
                        'country' => $view->ip_country,
                        'device_type' => $view->device_type,
                        'viewed_at' => $view->viewed_at,
                    ];
                }),
        ];

        return $this->successResponse($stats, 'Overview statistics retrieved successfully');
    }

    public function models(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin', 'researcher'])) {
            return $this->forbiddenResponse('Admin access required');
        }

        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : Carbon::now()->subDays(30);
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : Carbon::now();

        $modelStats = ModelView::select(
                'viewable_type',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users'),
                DB::raw('COUNT(DISTINCT ip_address) as unique_ips'),
                DB::raw('MAX(viewed_at) as last_viewed'),
                DB::raw('MIN(viewed_at) as first_viewed')
            )
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->groupBy('viewable_type')
            ->orderByDesc('total_views')
            ->get()
            ->map(function($stat) {
                return [
                    'model_type' => class_basename($stat->viewable_type),
                    'full_model_type' => $stat->viewable_type,
                    'total_views' => (int) $stat->total_views,
                    'unique_users' => (int) $stat->unique_users,
                    'unique_ips' => (int) $stat->unique_ips,
                    'first_viewed' => $stat->first_viewed,
                    'last_viewed' => $stat->last_viewed,
                ];
            });

        return $this->successResponse([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'models' => $modelStats,
        ], 'Model statistics retrieved successfully');
    }

    public function users(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin', 'researcher'])) {
            return $this->forbiddenResponse('Admin access required');
        }

        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : Carbon::now()->subDays(30);
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : Carbon::now();
        $limit = $validated['limit'] ?? 50;

        $topUsers = ModelView::select(
                'user_id',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT DATE(viewed_at)) as active_days'),
                DB::raw('MAX(viewed_at) as last_activity'),
                DB::raw('MIN(viewed_at) as first_activity')
            )
            ->with('user:id,name,email,role,created_at')
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->get()
            ->map(function($stat) use ($startDate, $endDate) {
                // Calculate unique content viewed separately for SQLite compatibility
                $uniqueContentViewed = ModelView::where('user_id', $stat->user_id)
                    ->whereBetween('viewed_at', [$startDate, $endDate])
                    ->select(DB::raw('DISTINCT viewable_type, viewable_id'))
                    ->get()
                    ->count();

                return [
                    'user' => $stat->user ? [
                        'id' => $stat->user->id,
                        'name' => $stat->user->name,
                        'email' => $stat->user->email,
                        'role' => $stat->user->role,
                        'member_since' => $stat->user->created_at,
                    ] : null,
                    'total_views' => (int) $stat->total_views,
                    'unique_content_viewed' => $uniqueContentViewed,
                    'active_days' => (int) $stat->active_days,
                    'first_activity' => $stat->first_activity,
                    'last_activity' => $stat->last_activity,
                ];
            });

        $roleBreakdown = ModelView::select(
                'users.role',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT model_views.user_id) as unique_users')
            )
            ->join('users', 'model_views.user_id', '=', 'users.id')
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->groupBy('users.role')
            ->get()
            ->map(function($stat) {
                return [
                    'role' => $stat->role,
                    'total_views' => (int) $stat->total_views,
                    'unique_users' => (int) $stat->unique_users,
                ];
            });

        return $this->successResponse([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'top_users' => $topUsers,
            'role_breakdown' => $roleBreakdown,
        ], 'User statistics retrieved successfully');
    }

    public function geography(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin', 'researcher'])) {
            return $this->forbiddenResponse('Admin access required');
        }

        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'group_by' => 'sometimes|string|in:country,region,city,continent,timezone',
        ]);

        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : Carbon::now()->subDays(30);
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : Carbon::now();
        $groupBy = $validated['group_by'] ?? 'country';

        $geoField = match($groupBy) {
            'country' => 'ip_country',
            'region' => 'ip_region', 
            'city' => 'ip_city',
            'continent' => 'ip_continent',
            'timezone' => 'ip_timezone',
            default => 'ip_country'
        };

        $geoCodeField = match($groupBy) {
            'country' => 'ip_country_code',
            'continent' => 'ip_continent_code',
            default => null
        };

        $selectFields = [
            $geoField . ' as location',
            DB::raw('COUNT(*) as total_views'),
            DB::raw('COUNT(DISTINCT user_id) as unique_users'),
            DB::raw('COUNT(DISTINCT ip_address) as unique_ips')
        ];

        if ($geoCodeField) {
            $selectFields[] = $geoCodeField . ' as location_code';
        }

        $geoStats = ModelView::select($selectFields)
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->whereNotNull($geoField)
            ->groupBy($geoCodeField ? [$geoField, $geoCodeField] : [$geoField])
            ->orderByDesc('total_views')
            ->limit(50)
            ->get()
            ->map(function($stat) use ($geoCodeField) {
                $result = [
                    'location' => $stat->location,
                    'total_views' => (int) $stat->total_views,
                    'unique_users' => (int) $stat->unique_users,
                    'unique_ips' => (int) $stat->unique_ips,
                ];
                
                if ($geoCodeField && isset($stat->location_code)) {
                    $result['location_code'] = $stat->location_code;
                }
                
                return $result;
            });

        return $this->successResponse([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'group_by' => $groupBy,
            'geographic_data' => $geoStats,
        ], 'Geographic statistics retrieved successfully');
    }

    public function devices(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin', 'researcher'])) {
            return $this->forbiddenResponse('Admin access required');
        }

        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : Carbon::now()->subDays(30);
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : Carbon::now();

        $deviceTypes = ModelView::select(
                'device_type',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users')
            )
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->orderByDesc('total_views')
            ->get();

        $platforms = ModelView::select(
                'device_platform',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users')
            )
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->whereNotNull('device_platform')
            ->groupBy('device_platform')
            ->orderByDesc('total_views')
            ->get();

        $browsers = ModelView::select(
                'device_browser',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users')
            )
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->whereNotNull('device_browser')
            ->groupBy('device_browser')
            ->orderByDesc('total_views')
            ->limit(20)
            ->get();

        return $this->successResponse([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'device_types' => $deviceTypes,
            'platforms' => $platforms,
            'browsers' => $browsers,
        ], 'Device statistics retrieved successfully');
    }

    public function trends(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin', 'researcher'])) {
            return $this->forbiddenResponse('Admin access required');
        }

        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'interval' => 'sometimes|string|in:hour,day,week,month',
            'model_type' => 'sometimes|string',
        ]);

        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : Carbon::now()->subDays(30);
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : Carbon::now();
        $interval = $validated['interval'] ?? 'day';

        // Database-agnostic date formatting
        $dbDriver = config('database.default');
        $isMySQL = $dbDriver === 'mysql';
        
        if ($isMySQL) {
            $dateFormat = match($interval) {
                'hour' => '%Y-%m-%d %H:00:00',
                'day' => '%Y-%m-%d',
                'week' => '%Y-%u',
                'month' => '%Y-%m',
                default => '%Y-%m-%d'
            };
            $dateFunction = "DATE_FORMAT(viewed_at, '$dateFormat')";
        } else {
            // SQLite
            $dateFormat = match($interval) {
                'hour' => '%Y-%m-%d %H:00:00',
                'day' => '%Y-%m-%d',
                'week' => '%Y-%W',
                'month' => '%Y-%m',
                default => '%Y-%m-%d'
            };
            $dateFunction = "strftime('$dateFormat', viewed_at)";
        }

        $query = ModelView::select(
                DB::raw("$dateFunction as period"),
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users'),
                DB::raw('COUNT(DISTINCT ip_address) as unique_ips')
            )
            ->whereBetween('viewed_at', [$startDate, $endDate]);

        if (isset($validated['model_type'])) {
            $query->where('viewable_type', $validated['model_type']);
        }

        $trends = $query->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function($stat) {
                return [
                    'period' => $stat->period,
                    'total_views' => (int) $stat->total_views,
                    'unique_users' => (int) $stat->unique_users,
                    'unique_ips' => (int) $stat->unique_ips,
                ];
            });

        return $this->successResponse([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'interval' => $interval,
            'model_type' => $validated['model_type'] ?? 'all',
            'trends' => $trends,
        ], 'Trend statistics retrieved successfully');
    }

    public function myActivity(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : Carbon::now()->subDays(30);
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : Carbon::now();
        $limit = $validated['limit'] ?? 50;

        $userViews = ModelView::with('viewable')
            ->where('user_id', $user->id)
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->latest('viewed_at')
            ->limit($limit)
            ->get()
            ->map(function($view) {
                return [
                    'id' => $view->id,
                    'model_type' => class_basename($view->viewable_type),
                    'model_id' => $view->viewable_id,
                    'model_title' => $view->viewable->title ?? $view->viewable->name ?? 'N/A',
                    'viewed_at' => $view->viewed_at,
                ];
            });

        $stats = [
            'total_views' => ModelView::where('user_id', $user->id)
                ->whereBetween('viewed_at', [$startDate, $endDate])
                ->count(),
            'unique_content' => ModelView::where('user_id', $user->id)
                ->whereBetween('viewed_at', [$startDate, $endDate])
                ->select(DB::raw('DISTINCT viewable_type, viewable_id'))
                ->get()
                ->count(),
            'most_viewed_model' => ModelView::select('viewable_type', DB::raw('COUNT(*) as count'))
                ->where('user_id', $user->id)
                ->whereBetween('viewed_at', [$startDate, $endDate])
                ->groupBy('viewable_type')
                ->orderByDesc('count')
                ->first(),
        ];

        return $this->successResponse([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'stats' => $stats,
            'recent_views' => $userViews,
        ], 'User activity retrieved successfully');
    }

    public function popular(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_type' => 'sometimes|string',
            'limit' => 'sometimes|integer|min:1|max:50',
            'period' => 'sometimes|string|in:today,week,month,all',
        ]);

        $limit = $validated['limit'] ?? 20;
        $period = $validated['period'] ?? 'week';

        $query = ModelView::select(
                'viewable_type',
                'viewable_id',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users'),
                DB::raw('MAX(viewed_at) as last_viewed')
            )
            ->with('viewable');

        if (isset($validated['model_type'])) {
            $query->where('viewable_type', $validated['model_type']);
        }

        switch ($period) {
            case 'today':
                $query->whereDate('viewed_at', Carbon::today());
                break;
            case 'week':
                $query->whereBetween('viewed_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('viewed_at', Carbon::now()->month)
                      ->whereYear('viewed_at', Carbon::now()->year);
                break;
        }

        $popular = $query->groupBy('viewable_type', 'viewable_id')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->get()
            ->map(function($stat) {
                return [
                    'model_type' => class_basename($stat->viewable_type),
                    'model_id' => $stat->viewable_id,
                    'model_title' => $stat->viewable->title ?? $stat->viewable->name ?? 'N/A',
                    'total_views' => (int) $stat->total_views,
                    'unique_users' => (int) $stat->unique_users,
                    'last_viewed' => $stat->last_viewed,
                ];
            });

        return $this->successResponse([
            'period' => $period,
            'model_type' => $validated['model_type'] ?? 'all',
            'popular_content' => $popular,
        ], 'Popular content retrieved successfully');
    }

    private function getDailyBreakdown(Carbon $startDate, Carbon $endDate, ?string $modelType = null): array
    {
        // Database-agnostic date formatting
        $dbDriver = config('database.default');
        $dateFunction = $dbDriver === 'mysql' 
            ? "DATE_FORMAT(viewed_at, '%Y-%m-%d')" 
            : "strftime('%Y-%m-%d', viewed_at)";

        $query = ModelView::select(
                DB::raw("$dateFunction as date"),
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users')
            )
            ->whereBetween('viewed_at', [$startDate, $endDate]);

        if ($modelType) {
            $query->where('viewable_type', $modelType);
        }

        return $query->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($stat) {
                return [
                    'date' => $stat->date,
                    'total_views' => (int) $stat->total_views,
                    'unique_users' => (int) $stat->unique_users,
                ];
            })->toArray();
    }

    private function getTopModels(Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        return ModelView::select(
                'viewable_type',
                'viewable_id',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users')
            )
            ->with('viewable')
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->groupBy('viewable_type', 'viewable_id')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->get()
            ->map(function($stat) {
                return [
                    'model_type' => class_basename($stat->viewable_type),
                    'model_id' => $stat->viewable_id,
                    'model_title' => $stat->viewable->title ?? $stat->viewable->name ?? 'N/A',
                    'total_views' => (int) $stat->total_views,
                    'unique_users' => (int) $stat->unique_users,
                ];
            })->toArray();
    }

    private function getTimeRange(string $timeFilter, ?string $customStart = null, ?string $customEnd = null): array
    {
        $periods = $this->calculateTimePeriods($timeFilter, $customStart, $customEnd);
        return [
            'start' => $periods['current']['start'],
            'end' => $periods['current']['end'],
        ];
    }

    private function calculateTimePeriods(string $timeFilter, ?string $customStart = null, ?string $customEnd = null): array
    {
        $now = Carbon::now();
        
        return match($timeFilter) {
            'today' => [
                'current' => [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ],
                'comparison' => [
                    'start' => $now->copy()->subDay()->startOfDay(),
                    'end' => $now->copy()->subDay()->endOfDay(),
                ],
                'labels' => [
                    'current' => 'Today',
                    'comparison' => 'Yesterday',
                ],
            ],
            'this_week' => [
                'current' => [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now,
                ],
                'comparison' => [
                    'start' => $now->copy()->subWeek()->startOfWeek(),
                    'end' => $now->copy()->subWeek()->endOfWeek(),
                ],
                'labels' => [
                    'current' => 'This Week',
                    'comparison' => 'Last Week',
                ],
            ],
            'this_month' => [
                'current' => [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now,
                ],
                'comparison' => [
                    'start' => $now->copy()->subMonth()->startOfMonth(),
                    'end' => $now->copy()->subMonth()->endOfMonth(),
                ],
                'labels' => [
                    'current' => 'This Month',
                    'comparison' => 'Last Month',
                ],
            ],
            'last_24h' => [
                'current' => [
                    'start' => $now->copy()->subHours(24),
                    'end' => $now,
                ],
                'comparison' => [
                    'start' => $now->copy()->subHours(48),
                    'end' => $now->copy()->subHours(24),
                ],
                'labels' => [
                    'current' => 'Last 24h',
                    'comparison' => 'Previous 24h',
                ],
            ],
            'last_7d' => [
                'current' => [
                    'start' => $now->copy()->subDays(7),
                    'end' => $now,
                ],
                'comparison' => [
                    'start' => $now->copy()->subDays(14),
                    'end' => $now->copy()->subDays(7),
                ],
                'labels' => [
                    'current' => 'Last 7d',
                    'comparison' => 'Previous 7d',
                ],
            ],
            'last_30d' => [
                'current' => [
                    'start' => $now->copy()->subDays(30),
                    'end' => $now,
                ],
                'comparison' => [
                    'start' => $now->copy()->subDays(60),
                    'end' => $now->copy()->subDays(30),
                ],
                'labels' => [
                    'current' => 'Last 30d',
                    'comparison' => 'Previous 30d',
                ],
            ],
            'custom' => [
                'current' => [
                    'start' => $customStart ? Carbon::parse($customStart) : $now->copy()->subDays(7),
                    'end' => $customEnd ? Carbon::parse($customEnd) : $now,
                ],
                'comparison' => [
                    'start' => $customStart ? Carbon::parse($customStart)->copy()->subDays(Carbon::parse($customStart)->diffInDays(Carbon::parse($customEnd ?? $now))) : $now->copy()->subDays(14),
                    'end' => $customStart ? Carbon::parse($customStart) : $now->copy()->subDays(7),
                ],
                'labels' => [
                    'current' => 'Selected Period',
                    'comparison' => 'Previous Period',
                ],
            ],
        };
    }

    private function calculatePeriodMetrics($baseQuery, Carbon $start, Carbon $end): array
    {
        $query = clone $baseQuery;
        $query->whereBetween('viewed_at', [$start, $end]);
        
        return [
            'total_views' => $query->count(),
            'unique_users' => (clone $query)->distinct('user_id')->whereNotNull('user_id')->count(),
            'guest_views' => (clone $query)->whereHas('user', function($q) {
                $q->where('role', 'guest');
            })->count(),
            'registered_views' => (clone $query)->whereHas('user', function($q) {
                $q->where('role', '!=', 'guest');
            })->count(),
            'bot_views' => (clone $query)->where('is_bot', true)->count(),
            'human_views' => (clone $query)->where(function($q) {
                $q->where('is_bot', false)->orWhereNull('is_bot');
            })->count(),
            'search_engine_views' => (clone $query)->where('is_search_engine', true)->count(),
            'social_media_views' => (clone $query)->where('is_social_media', true)->count(),
        ];
    }

    private function calculatePercentageChange($previous, $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getDetailedAnalytics($baseQuery, Carbon $start, Carbon $end): array
    {
        $query = clone $baseQuery;
        $query->whereBetween('viewed_at', [$start, $end]);
        
        return [
            'top_countries' => (clone $query)
                ->select('ip_country', DB::raw('COUNT(*) as views'))
                ->whereNotNull('ip_country')
                ->groupBy('ip_country')
                ->orderByDesc('views')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'country' => $item->ip_country,
                        'views' => (int) $item->views,
                    ];
                }),
            'top_devices' => (clone $query)
                ->select('device_type', DB::raw('COUNT(*) as views'))
                ->whereNotNull('device_type')
                ->groupBy('device_type')
                ->orderByDesc('views')
                ->get()
                ->map(function($item) {
                    return [
                        'device_type' => $item->device_type,
                        'views' => (int) $item->views,
                    ];
                }),
            'top_content' => (clone $query)
                ->select('viewable_type', 'viewable_id', DB::raw('COUNT(*) as views'))
                ->with('viewable')
                ->groupBy('viewable_type', 'viewable_id')
                ->orderByDesc('views')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'type' => class_basename($item->viewable_type),
                        'id' => $item->viewable_id,
                        'title' => $item->viewable->title ?? $item->viewable->name ?? 'N/A',
                        'views' => (int) $item->views,
                    ];
                }),
            'hourly_distribution' => $this->getHourlyDistribution(clone $query),
            'bot_breakdown' => [
                'total_bot_views' => (clone $query)->where('is_bot', true)->count(),
                'total_human_views' => (clone $query)->where(function($q) {
                    $q->where('is_bot', false)->orWhereNull('is_bot');
                })->count(),
                'search_engine_bots' => (clone $query)->where('is_search_engine', true)->count(),
                'social_media_bots' => (clone $query)->where('is_social_media', true)->count(),
                'top_bots' => (clone $query)
                    ->select('bot_name', DB::raw('COUNT(*) as views'))
                    ->where('is_bot', true)
                    ->whereNotNull('bot_name')
                    ->groupBy('bot_name')
                    ->orderByDesc('views')
                    ->limit(10)
                    ->get()
                    ->map(function($item) {
                        return [
                            'bot_name' => $item->bot_name,
                            'views' => (int) $item->views,
                        ];
                    }),
            ],
        ];
    }

    private function getHourlyDistribution($query): array
    {
        $dbDriver = config('database.default');
        $hourFunction = $dbDriver === 'mysql' 
            ? "HOUR(viewed_at)" 
            : "strftime('%H', viewed_at)";

        return $query->select(
                DB::raw("$hourFunction as hour"),
                DB::raw('COUNT(*) as views')
            )
            ->groupBy(DB::raw("$hourFunction"))
            ->orderBy(DB::raw("$hourFunction"))
            ->get()
            ->map(function($item) {
                return [
                    'hour' => (int) $item->hour,
                    'views' => (int) $item->views,
                ];
            })
            ->toArray();
    }
}