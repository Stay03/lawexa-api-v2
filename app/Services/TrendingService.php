<?php

namespace App\Services;

use App\Models\ModelView;
use App\Models\User;
use App\Models\CourtCase;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use App\Models\Note;
use App\Models\Folder;
use App\Models\Comment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrendingService
{
    private const SUPPORTED_TYPES = [
        'cases' => CourtCase::class,
        'statutes' => Statute::class,
        'divisions' => StatuteDivision::class,
        'provisions' => StatuteProvision::class,
        'notes' => Note::class,
        'folders' => Folder::class,
        'comments' => Comment::class,
    ];

    private const TIME_RANGES = [
        'today' => 1,
        'week' => 7,
        'month' => 30,
        'year' => 365,
    ];

    public function getTrendingContent(array $filters = []): LengthAwarePaginator
    {
        $contentType = $filters['type'] ?? 'all';
        $timeRange = $filters['time_range'] ?? 'week';
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        // Get date range for trend calculation
        $dateRange = $this->getDateRange($timeRange, $filters);

        if ($contentType === 'all') {
            return $this->getMixedTrendingContent($filters, $dateRange, $perPage, $page);
        }

        return $this->getSingleTypeTrendingContent($contentType, $filters, $dateRange, $perPage, $page);
    }

    private function getMixedTrendingContent(array $filters, array $dateRange, int $perPage, int $page): LengthAwarePaginator
    {
        $allTrendingItems = collect();

        foreach (self::SUPPORTED_TYPES as $type => $modelClass) {
            $trending = $this->calculateTrendingForType($modelClass, $filters, $dateRange);
            
            // Add type information to each item
            $trending->each(function ($item) use ($type) {
                $item->content_type = $type;
                $item->trending_score = $this->calculateTrendingScore($item);
            });
            
            $allTrendingItems = $allTrendingItems->concat($trending);
        }

        // Sort by trending score and paginate
        $sortedItems = $allTrendingItems->sortByDesc('trending_score')->values();
        
        return $this->paginateCollection($sortedItems, $perPage, $page);
    }

    private function getSingleTypeTrendingContent(string $contentType, array $filters, array $dateRange, int $perPage, int $page): LengthAwarePaginator
    {
        if (!isset(self::SUPPORTED_TYPES[$contentType])) {
            throw new \InvalidArgumentException("Unsupported content type: {$contentType}");
        }

        $modelClass = self::SUPPORTED_TYPES[$contentType];
        $trending = $this->calculateTrendingForType($modelClass, $filters, $dateRange);
        
        // Add trending score and type
        $trending->each(function ($item) use ($contentType) {
            $item->content_type = $contentType;
            $item->trending_score = $this->calculateTrendingScore($item);
        });

        $sortedItems = $trending->sortByDesc('trending_score')->values();
        
        return $this->paginateCollection($sortedItems, $perPage, $page);
    }

    private function calculateTrendingForType(string $modelClass, array $filters, array $dateRange): Collection
    {
        $viewsQuery = ModelView::query()
            ->where('viewable_type', $modelClass)
            ->whereBetween('viewed_at', [$dateRange['start'], $dateRange['end']])
            ->when($filters['country'] ?? null, function ($query, $country) {
                if (strlen(trim($country)) === 2) {
                    // If it looks like a country code, search by code
                    $query->where('ip_country_code', strtoupper($country));
                } else {
                    // Search by country name with LIKE for flexibility
                    $query->where('ip_country', 'like', '%' . $country . '%');
                }
            })
            ->when($filters['university'] ?? null, function ($query, $university) {
                $query->whereHas('user', function ($userQuery) use ($university) {
                    $userQuery->where('university', $university);
                });
            })
            ->when($filters['level'] ?? null, function ($query, $level) {
                $query->whereHas('user', function ($userQuery) use ($level) {
                    $userQuery->where('level', $level);
                });
            });

        // Group by viewable_id and calculate trending metrics
        $trendingData = $viewsQuery
            ->select([
                'viewable_id',
                'viewable_type',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT user_id) as unique_viewers'),
                DB::raw('MAX(viewed_at) as latest_view'),
                DB::raw('MIN(viewed_at) as earliest_view'),
                // Weight recent views more heavily (SQLite compatible)
                DB::raw('SUM(CASE
                    WHEN viewed_at >= datetime("now", "-1 day") THEN 3
                    WHEN viewed_at >= datetime("now", "-3 days") THEN 2
                    ELSE 1
                END) as weighted_score')
            ])
            ->groupBy('viewable_id', 'viewable_type')
            ->having('total_views', '>=', 2) // Minimum view threshold
            ->orderByDesc('weighted_score')
            ->limit(200) // Get top 200 for further processing
            ->get();

        // Load the actual models with their data
        $modelIds = $trendingData->pluck('viewable_id');
        $user = request()->user();

        // Determine counts to load based on model type
        $countsToLoad = ['bookmarks'];
        if ($modelClass === Folder::class) {
            $countsToLoad[] = 'items';
        }

        $models = $modelClass::whereIn('id', $modelIds)
            ->with($this->getModelRelations($modelClass))
            ->withCount($countsToLoad)
            ->when($user, function ($query) use ($user) {
                $query->withUserBookmark($user);
            })
            ->get()
            ->keyBy('id');

        // Combine trending data with model data
        return $trendingData->map(function ($trendingItem) use ($models) {
            $model = $models[$trendingItem->viewable_id] ?? null;

            if (!$model) {
                return null;
            }

            // Add trending metrics to the model
            $model->total_views = $trendingItem->total_views;
            $model->unique_viewers = $trendingItem->unique_viewers;
            $model->latest_view = $trendingItem->latest_view;
            $model->earliest_view = $trendingItem->earliest_view;
            $model->weighted_score = $trendingItem->weighted_score;

            return $model;
        })->filter();
    }

    private function calculateTrendingScore($item): float
    {
        $totalViews = $item->total_views ?? 0;
        $uniqueViewers = $item->unique_viewers ?? 0;
        $weightedScore = $item->weighted_score ?? 0;
        $latestView = $item->latest_view ? Carbon::parse($item->latest_view) : null;

        // Base score from weighted views
        $score = $weightedScore;

        // Boost for unique viewers (engagement diversity)
        $uniqueBoost = $uniqueViewers > 1 ? ($uniqueViewers / $totalViews) * 10 : 0;
        $score += $uniqueBoost;

        // Recency boost (items viewed recently get higher scores)
        if ($latestView) {
            $hoursAgo = Carbon::now()->diffInHours($latestView);
            $recencyBoost = max(0, 24 - $hoursAgo) / 24 * 5; // 5 point max boost for very recent
            $score += $recencyBoost;
        }

        return round($score, 2);
    }

    private function getDateRange(string $timeRange, array $filters): array
    {
        if ($timeRange === 'custom') {
            $startDate = isset($filters['start_date']) 
                ? Carbon::parse($filters['start_date']) 
                : Carbon::now()->subDays(7);
            $endDate = isset($filters['end_date']) 
                ? Carbon::parse($filters['end_date']) 
                : Carbon::now();
        } else {
            $days = self::TIME_RANGES[$timeRange] ?? 7;
            $startDate = Carbon::now()->subDays($days);
            $endDate = Carbon::now();
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    private function getModelRelations(string $modelClass): array
    {
        return match ($modelClass) {
            CourtCase::class => ['creator:id,name', 'files:id,viewable_id,file_name,file_size'],
            Statute::class => ['creator:id,name', 'files:id,viewable_id,file_name,file_size'],
            StatuteDivision::class => ['statute:id,title,slug', 'parentDivision'],
            StatuteProvision::class => ['division', 'statute:id,title,slug', 'parentProvision'],
            Note::class => ['user:id,name,avatar'],
            Folder::class => ['user:id,name,avatar'],
            Comment::class => ['user:id,name'],
            default => [],
        };
    }

    private function paginateCollection(Collection $collection, int $perPage, int $page): LengthAwarePaginator
    {
        $offset = ($page - 1) * $perPage;
        $items = $collection->slice($offset, $perPage)->values();
        
        return new LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    public function getTrendingStats(array $filters = []): array
    {
        $timeRange = $filters['time_range'] ?? 'week';
        $dateRange = $this->getDateRange($timeRange, $filters);

        $baseQuery = ModelView::query()
            ->whereBetween('viewed_at', [$dateRange['start'], $dateRange['end']])
            ->when($filters['country'] ?? null, function ($query, $country) {
                if (strlen(trim($country)) === 2) {
                    // If it looks like a country code, search by code
                    $query->where('ip_country_code', strtoupper($country));
                } else {
                    // Search by country name with LIKE for flexibility
                    $query->where('ip_country', 'like', '%' . $country . '%');
                }
            })
            ->when($filters['university'] ?? null, function ($query, $university) {
                $query->whereHas('user', function ($userQuery) use ($university) {
                    $userQuery->where('university', $university);
                });
            })
            ->when($filters['level'] ?? null, function ($query, $level) {
                $query->whereHas('user', function ($userQuery) use ($level) {
                    $userQuery->where('level', $level);
                });
            });

        $stats = [];
        foreach (self::SUPPORTED_TYPES as $type => $modelClass) {
            $count = (clone $baseQuery)->where('viewable_type', $modelClass)->count();
            $stats[$type] = $count;
        }

        $stats['total'] = array_sum($stats);
        $stats['time_range'] = $timeRange;
        $stats['date_range'] = [
            'start' => $dateRange['start']->toDateString(),
            'end' => $dateRange['end']->toDateString(),
        ];

        return $stats;
    }
}