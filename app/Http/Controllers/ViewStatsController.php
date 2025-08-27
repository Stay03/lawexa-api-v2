<?php

namespace App\Http\Controllers;

use App\Models\ModelView;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ViewStatsController extends Controller
{
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
}