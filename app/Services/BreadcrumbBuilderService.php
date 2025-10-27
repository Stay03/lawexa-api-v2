<?php

namespace App\Services;

use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Breadcrumb Builder Service
 *
 * Builds hierarchical breadcrumb trails from statute root to target content.
 * Uses aggressive caching with tag-based invalidation for performance.
 */
class BreadcrumbBuilderService
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Build breadcrumb trail for given content
     *
     * @param Model $content StatuteDivision or StatuteProvision
     * @param Statute|null $statute If not provided, will be loaded from content
     * @return array
     */
    public function build(Model $content, ?Statute $statute = null): array
    {
        // Determine content type
        $type = $content instanceof StatuteDivision ? 'division' : 'provision';

        // Get statute if not provided
        if (!$statute) {
            $statute = $content->statute;
        }

        // Cache key pattern: breadcrumb:{statute_id}:{type}:{content_id}
        $cacheKey = "breadcrumb:{$statute->id}:{$type}:{$content->id}";
        $ttl = config('statute.cache.breadcrumb_ttl', self::CACHE_TTL);

        $callback = function () use ($content, $statute, $type) {
            return $this->buildBreadcrumbPath($content, $statute, $type);
        };

        if (config('statute.cache.tags_enabled', false)) {
            return Cache::tags(["statute:{$statute->id}"])->remember($cacheKey, $ttl, $callback);
        }

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Build breadcrumb path without caching
     *
     * @param Model $content
     * @param Statute $statute
     * @param string $type
     * @return array
     */
    private function buildBreadcrumbPath(Model $content, Statute $statute, string $type): array
    {
        $breadcrumb = [];

        // Start with the statute root
        $breadcrumb[] = [
            'id' => $statute->id,
            'slug' => $statute->slug,
            'title' => $statute->title,
            'type' => 'statute',
            'order_index' => null
        ];

        if ($type === 'division') {
            // Build division path
            $divisionPath = $this->buildDivisionPath($content);
            $breadcrumb = array_merge($breadcrumb, $divisionPath);
        } else {
            // Provision: need to build division path first, then provision path
            if ($content->division_id) {
                $division = StatuteDivision::find($content->division_id);
                if ($division) {
                    $divisionPath = $this->buildDivisionPath($division);
                    $breadcrumb = array_merge($breadcrumb, $divisionPath);
                }
            }

            // Now build provision path
            $provisionPath = $this->buildProvisionPath($content);
            $breadcrumb = array_merge($breadcrumb, $provisionPath);
        }

        return $breadcrumb;
    }

    /**
     * Build division path from current to root
     *
     * @param StatuteDivision $division
     * @return array
     */
    private function buildDivisionPath(StatuteDivision $division): array
    {
        $path = [];
        $current = $division;

        // Walk up the parent chain
        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'slug' => $current->slug,
                'title' => $current->division_title,
                'number' => $current->division_number,
                'type' => $current->division_type,
                'order_index' => $current->order_index
            ]);

            // Load parent if exists
            if ($current->parent_division_id) {
                $current = StatuteDivision::find($current->parent_division_id);
            } else {
                $current = null;
            }
        }

        return $path;
    }

    /**
     * Build provision path from current to root
     *
     * @param StatuteProvision $provision
     * @return array
     */
    private function buildProvisionPath(StatuteProvision $provision): array
    {
        $path = [];
        $current = $provision;

        // Walk up the parent chain
        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'slug' => $current->slug,
                'title' => $current->provision_title,
                'number' => $current->provision_number,
                'type' => $current->provision_type,
                'order_index' => $current->order_index
            ]);

            // Load parent if exists
            if ($current->parent_provision_id) {
                $current = StatuteProvision::find($current->parent_provision_id);
            } else {
                $current = null;
            }
        }

        return $path;
    }

    /**
     * Invalidate breadcrumb cache for specific content
     *
     * @param Model $content
     * @param Statute|null $statute
     * @return void
     */
    public function invalidate(Model $content, ?Statute $statute = null): void
    {
        $type = $content instanceof StatuteDivision ? 'division' : 'provision';

        if (!$statute) {
            $statute = $content->statute;
        }

        $cacheKey = "breadcrumb:{$statute->id}:{$type}:{$content->id}";

        if (config('statute.cache.tags_enabled', false)) {
            Cache::tags(["statute:{$statute->id}"])->forget($cacheKey);
        } else {
            Cache::forget($cacheKey);
        }

        // Also invalidate descendants
        $this->invalidateDescendants($content, $statute, $type);
    }

    /**
     * Invalidate breadcrumb cache for all descendants
     *
     * @param Model $content
     * @param Statute $statute
     * @param string $type
     * @return void
     */
    private function invalidateDescendants(Model $content, Statute $statute, string $type): void
    {
        if ($type === 'division') {
            // Invalidate child divisions
            $childDivisions = StatuteDivision::where('parent_division_id', $content->id)->get();
            foreach ($childDivisions as $child) {
                $this->invalidate($child, $statute);
            }

            // Invalidate provisions under this division
            $provisions = StatuteProvision::where('division_id', $content->id)->get();
            foreach ($provisions as $provision) {
                $this->invalidate($provision, $statute);
            }
        } else {
            // Invalidate child provisions
            $childProvisions = StatuteProvision::where('parent_provision_id', $content->id)->get();
            foreach ($childProvisions as $child) {
                $this->invalidate($child, $statute);
            }
        }
    }

    /**
     * Invalidate all breadcrumbs for a statute
     *
     * @param Statute $statute
     * @return void
     */
    public function invalidateStatute(Statute $statute): void
    {
        if (config('statute.cache.tags_enabled', false)) {
            Cache::tags(["statute:{$statute->id}"])->flush();
        } else {
            // Without tags, we can only clear the entire cache or skip invalidation
            // For production, use cache tags with Redis/Memcached
            // For now, we'll skip invalidation when tags are disabled
        }
    }

    /**
     * Build breadcrumb for statute only (root level)
     *
     * @param Statute $statute
     * @return array
     */
    public function buildStatuteBreadcrumb(Statute $statute): array
    {
        return [
            [
                'id' => $statute->id,
                'slug' => $statute->slug,
                'title' => $statute->title,
                'type' => 'statute',
                'order_index' => null
            ]
        ];
    }
}
