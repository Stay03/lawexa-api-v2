<?php

namespace App\Services;

use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Content Resolver Service
 *
 * Resolves any content (division or provision) by slug and returns unified metadata.
 * This is the foundation of hash-first lazy loading.
 */
class ContentResolverService
{
    /**
     * Resolve content by slug
     *
     * @param Statute $statute
     * @param string $slug
     * @return array Content resolution data
     * @throws NotFoundHttpException
     */
    public function resolveBySlug(Statute $statute, string $slug): array
    {
        // Try to find in divisions first
        $division = StatuteDivision::where('statute_id', $statute->id)
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if ($division) {
            return $this->buildResolution($statute, $division, 'division');
        }

        // Try to find in provisions
        $provision = StatuteProvision::where('statute_id', $statute->id)
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if ($provision) {
            return $this->buildResolution($statute, $provision, 'provision');
        }

        // Not found
        throw new NotFoundHttpException("Content with slug '{$slug}' not found in statute '{$statute->slug}'");
    }

    /**
     * Build resolution data for content
     *
     * @param Statute $statute
     * @param Model $content
     * @param string $type
     * @return array
     */
    private function buildResolution(Statute $statute, Model $content, string $type): array
    {
        $orderIndex = $content->order_index;

        if ($orderIndex === null) {
            throw new \RuntimeException("Content {$content->id} has no order_index. Run statutes:populate-order-index command.");
        }

        // Get position metadata
        $positionMetadata = $this->getPositionMetadata($statute, $orderIndex);

        return [
            'type' => $type,
            'content' => $content,
            'order_index' => $orderIndex,
            'position' => $positionMetadata
        ];
    }

    /**
     * Get position metadata for an order index
     *
     * @param Statute $statute
     * @param int $orderIndex
     * @return array
     */
    public function getPositionMetadata(Statute $statute, int $orderIndex): array
    {
        $cacheKey = "position:{$statute->id}:{$orderIndex}";
        $ttl = config('statute.cache.position_ttl', 1800);

        $callback = function () use ($statute, $orderIndex) {
            return [
                'order_index' => $orderIndex,
                'total_items' => $this->getTotalItems($statute),
                'has_content_before' => $this->hasContentBefore($statute, $orderIndex),
                'has_content_after' => $this->hasContentAfter($statute, $orderIndex)
            ];
        };

        if (config('statute.cache.tags_enabled', false)) {
            return Cache::tags(["statute:{$statute->id}"])->remember($cacheKey, $ttl, $callback);
        }

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get total items count for a statute
     *
     * @param Statute $statute
     * @return int
     */
    public function getTotalItems(Statute $statute): int
    {
        $cacheKey = "total_items:{$statute->id}";
        $ttl = config('statute.cache.total_items_ttl', 3600);

        $callback = function () use ($statute) {
            $divisionsCount = StatuteDivision::where('statute_id', $statute->id)
                ->whereNotNull('order_index')
                ->where('status', 'active')
                ->count();

            $provisionsCount = StatuteProvision::where('statute_id', $statute->id)
                ->whereNotNull('order_index')
                ->where('status', 'active')
                ->count();

            return $divisionsCount + $provisionsCount;
        };

        if (config('statute.cache.tags_enabled', false)) {
            return Cache::tags(["statute:{$statute->id}"])->remember($cacheKey, $ttl, $callback);
        }

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Check if there is content before the given order index
     *
     * @param Statute $statute
     * @param int $orderIndex
     * @return bool
     */
    private function hasContentBefore(Statute $statute, int $orderIndex): bool
    {
        $hasDivisionBefore = StatuteDivision::where('statute_id', $statute->id)
            ->where('order_index', '<', $orderIndex)
            ->where('status', 'active')
            ->exists();

        if ($hasDivisionBefore) {
            return true;
        }

        return StatuteProvision::where('statute_id', $statute->id)
            ->where('order_index', '<', $orderIndex)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if there is content after the given order index
     *
     * @param Statute $statute
     * @param int $orderIndex
     * @return bool
     */
    private function hasContentAfter(Statute $statute, int $orderIndex): bool
    {
        $hasDivisionAfter = StatuteDivision::where('statute_id', $statute->id)
            ->where('order_index', '>', $orderIndex)
            ->where('status', 'active')
            ->exists();

        if ($hasDivisionAfter) {
            return true;
        }

        return StatuteProvision::where('statute_id', $statute->id)
            ->where('order_index', '>', $orderIndex)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Resolve content by order index
     *
     * @param Statute $statute
     * @param int $orderIndex
     * @return array|null
     */
    public function resolveByOrderIndex(Statute $statute, int $orderIndex): ?array
    {
        // Try to find in divisions first
        $division = StatuteDivision::where('statute_id', $statute->id)
            ->where('order_index', $orderIndex)
            ->where('status', 'active')
            ->first();

        if ($division) {
            return $this->buildResolution($statute, $division, 'division');
        }

        // Try to find in provisions
        $provision = StatuteProvision::where('statute_id', $statute->id)
            ->where('order_index', $orderIndex)
            ->where('status', 'active')
            ->first();

        if ($provision) {
            return $this->buildResolution($statute, $provision, 'provision');
        }

        return null;
    }
}
