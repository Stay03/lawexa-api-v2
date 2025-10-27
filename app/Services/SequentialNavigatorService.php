<?php

namespace App\Services;

use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use Illuminate\Support\Facades\DB;

/**
 * Sequential Navigator Service
 *
 * Loads content before or after a given position using efficient UNION queries.
 * This is the core of bidirectional lazy loading.
 */
class SequentialNavigatorService
{
    /**
     * Maximum items per request
     */
    private const MAX_LIMIT = 50;

    /**
     * Load content before a given order index
     *
     * @param Statute $statute
     * @param int $fromOrder
     * @param int $limit
     * @param bool $includeChildren
     * @return array
     */
    public function loadBefore(
        Statute $statute,
        int $fromOrder,
        int $limit = 5,
        bool $includeChildren = true
    ): array {
        $limit = min($limit, self::MAX_LIMIT);

        // Build UNION query for content before the given order
        $items = $this->buildUnionQuery($statute, $fromOrder, 'before', $limit, $includeChildren);

        // Check if there's more content before
        $hasMore = $this->hasContentBefore($statute, $items);

        // Get next from_order for pagination
        $nextFromOrder = !empty($items) ? min(array_column($items, 'order_index')) : null;

        return [
            'items' => $items,
            'meta' => [
                'direction' => 'before',
                'from_order' => $fromOrder,
                'limit' => $limit,
                'returned' => count($items),
                'has_more' => $hasMore,
                'next_from_order' => $hasMore ? $nextFromOrder : null
            ]
        ];
    }

    /**
     * Load content after a given order index
     *
     * @param Statute $statute
     * @param int $fromOrder
     * @param int $limit
     * @param bool $includeChildren
     * @return array
     */
    public function loadAfter(
        Statute $statute,
        int $fromOrder,
        int $limit = 5,
        bool $includeChildren = true
    ): array {
        $limit = min($limit, self::MAX_LIMIT);

        // Build UNION query for content after the given order
        $items = $this->buildUnionQuery($statute, $fromOrder, 'after', $limit, $includeChildren);

        // Check if there's more content after
        $hasMore = $this->hasContentAfter($statute, $items);

        // Get next from_order for pagination
        $nextFromOrder = !empty($items) ? max(array_column($items, 'order_index')) : null;

        return [
            'items' => $items,
            'meta' => [
                'direction' => 'after',
                'from_order' => $fromOrder,
                'limit' => $limit,
                'returned' => count($items),
                'has_more' => $hasMore,
                'next_from_order' => $hasMore ? $nextFromOrder : null
            ]
        ];
    }

    /**
     * Load content within a range of order indices
     *
     * @param Statute $statute
     * @param int $startOrder
     * @param int $endOrder
     * @param bool $includeChildren
     * @return array
     */
    public function loadRange(
        Statute $statute,
        int $startOrder,
        int $endOrder,
        bool $includeChildren = true
    ): array {
        if ($endOrder < $startOrder) {
            throw new \InvalidArgumentException('end_order must be greater than or equal to start_order');
        }

        // Note: We don't validate the numeric range (endOrder - startOrder) because
        // order indices can be sparse (e.g., 100, 200, 300...). Instead, we validate
        // the actual count of returned items to ensure we don't exceed the limit.

        // Build UNION query for range
        $items = $this->buildRangeQuery($statute, $startOrder, $endOrder, $includeChildren);

        // Validate that we don't return more than 100 actual items
        if (count($items) > 100) {
            throw new \InvalidArgumentException('Range contains more than 100 items. Please use a smaller range.');
        }

        // Get total items in statute
        $totalItems = $this->getTotalItems($statute);

        return [
            'items' => $items,
            'meta' => [
                'start_order' => $startOrder,
                'end_order' => $endOrder,
                'returned' => count($items),
                'total_items_in_statute' => $totalItems
            ]
        ];
    }

    /**
     * Build UNION query for sequential content
     *
     * @param Statute $statute
     * @param int $fromOrder
     * @param string $direction 'before' or 'after'
     * @param int $limit
     * @param bool $includeChildren
     * @return array
     */
    private function buildUnionQuery(
        Statute $statute,
        int $fromOrder,
        string $direction,
        int $limit,
        bool $includeChildren
    ): array {
        $operator = $direction === 'before' ? '<' : '>';
        $orderDirection = $direction === 'before' ? 'DESC' : 'ASC';

        // Build the UNION query using raw SQL for performance
        // Wrapped in subquery for SQLite compatibility
        $query = "
            SELECT * FROM (
                SELECT
                    'division' as content_type,
                    id,
                    slug,
                    order_index,
                    division_type as type_name,
                    division_number as number,
                    division_title as title,
                    division_subtitle as subtitle,
                    content,
                    level,
                    parent_division_id as parent_id,
                    NULL as division_id,
                    NULL as provision_text,
                    status,
                    created_at,
                    updated_at
                FROM statute_divisions
                WHERE statute_id = ? AND order_index {$operator} ? AND status = 'active'

                UNION ALL

                SELECT
                    'provision' as content_type,
                    id,
                    slug,
                    order_index,
                    provision_type as type_name,
                    provision_number as number,
                    provision_title as title,
                    NULL as subtitle,
                    NULL as content,
                    level,
                    parent_provision_id as parent_id,
                    division_id,
                    provision_text,
                    status,
                    created_at,
                    updated_at
                FROM statute_provisions
                WHERE statute_id = ? AND order_index {$operator} ? AND status = 'active'
            ) AS combined
            ORDER BY order_index {$orderDirection}
            LIMIT ?
        ";

        $results = DB::select($query, [
            $statute->id,
            $fromOrder,
            $statute->id,
            $fromOrder,
            $limit
        ]);

        // Transform results and optionally load children
        return $this->transformResults($results, $includeChildren);
    }

    /**
     * Build UNION query for range
     *
     * @param Statute $statute
     * @param int $startOrder
     * @param int $endOrder
     * @param bool $includeChildren
     * @return array
     */
    private function buildRangeQuery(
        Statute $statute,
        int $startOrder,
        int $endOrder,
        bool $includeChildren
    ): array {
        // Wrapped in subquery for SQLite compatibility
        $query = "
            SELECT * FROM (
                SELECT
                    'division' as content_type,
                    id,
                    slug,
                    order_index,
                    division_type as type_name,
                    division_number as number,
                    division_title as title,
                    division_subtitle as subtitle,
                    content,
                    level,
                    parent_division_id as parent_id,
                    NULL as division_id,
                    NULL as provision_text,
                    status,
                    created_at,
                    updated_at
                FROM statute_divisions
                WHERE statute_id = ? AND order_index BETWEEN ? AND ? AND status = 'active'

                UNION ALL

                SELECT
                    'provision' as content_type,
                    id,
                    slug,
                    order_index,
                    provision_type as type_name,
                    provision_number as number,
                    provision_title as title,
                    NULL as subtitle,
                    NULL as content,
                    level,
                    parent_provision_id as parent_id,
                    division_id,
                    provision_text,
                    status,
                    created_at,
                    updated_at
                FROM statute_provisions
                WHERE statute_id = ? AND order_index BETWEEN ? AND ? AND status = 'active'
            ) AS combined
            ORDER BY order_index ASC
        ";

        $results = DB::select($query, [
            $statute->id,
            $startOrder,
            $endOrder,
            $statute->id,
            $startOrder,
            $endOrder
        ]);

        return $this->transformResults($results, $includeChildren);
    }

    /**
     * Transform query results into structured array
     *
     * @param array $results
     * @param bool $includeChildren
     * @return array
     */
    private function transformResults(array $results, bool $includeChildren): array
    {
        $items = [];

        foreach ($results as $result) {
            $item = [
                'order_index' => $result->order_index,
                'type' => $result->content_type,
                'content' => $this->formatContent($result),
                'children' => []
            ];

            // Load children if requested
            if ($includeChildren && $result->content_type === 'division') {
                $item['children'] = $this->loadDivisionChildren($result->id);
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Format content data
     *
     * @param object $result
     * @return array
     */
    private function formatContent(object $result): array
    {
        $content = [
            'id' => $result->id,
            'slug' => $result->slug,
            'order_index' => $result->order_index,
            'type_name' => $result->type_name,
            'number' => $result->number,
            'title' => $result->title,
            'level' => $result->level,
            'parent_id' => $result->parent_id,
            'status' => $result->status,
            'created_at' => $result->created_at,
            'updated_at' => $result->updated_at
        ];

        if ($result->content_type === 'division') {
            $content['subtitle'] = $result->subtitle;
            $content['content'] = $result->content;
            // Calculate has_children and child_count
            $content['has_children'] = $this->divisionHasChildren($result->id);
            $content['child_count'] = $this->getDivisionChildCount($result->id);
        } else {
            $content['provision_text'] = $result->provision_text;
            $content['division_id'] = $result->division_id;
            $content['has_children'] = $this->provisionHasChildren($result->id);
        }

        return $content;
    }

    /**
     * Load immediate children for a division
     *
     * @param int $divisionId
     * @return array
     */
    private function loadDivisionChildren(int $divisionId): array
    {
        $children = StatuteDivision::where('parent_division_id', $divisionId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->limit(10) // Limit immediate children to prevent large payloads
            ->get(['id', 'slug', 'division_type', 'division_number', 'division_title', 'order_index'])
            ->toArray();

        return $children;
    }

    /**
     * Check if division has children
     *
     * @param int $divisionId
     * @return bool
     */
    private function divisionHasChildren(int $divisionId): bool
    {
        $hasChildDivisions = StatuteDivision::where('parent_division_id', $divisionId)
            ->where('status', 'active')
            ->exists();

        if ($hasChildDivisions) {
            return true;
        }

        return StatuteProvision::where('division_id', $divisionId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get child count for division
     *
     * @param int $divisionId
     * @return int
     */
    private function getDivisionChildCount(int $divisionId): int
    {
        $childDivisions = StatuteDivision::where('parent_division_id', $divisionId)
            ->where('status', 'active')
            ->count();

        $childProvisions = StatuteProvision::where('division_id', $divisionId)
            ->where('status', 'active')
            ->count();

        return $childDivisions + $childProvisions;
    }

    /**
     * Check if provision has children
     *
     * @param int $provisionId
     * @return bool
     */
    private function provisionHasChildren(int $provisionId): bool
    {
        return StatuteProvision::where('parent_provision_id', $provisionId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if there's more content before the returned items
     *
     * @param Statute $statute
     * @param array $items
     * @return bool
     */
    private function hasContentBefore(Statute $statute, array $items): bool
    {
        if (empty($items)) {
            return false;
        }

        $minOrderIndex = min(array_column($items, 'order_index'));

        $hasDivisionBefore = StatuteDivision::where('statute_id', $statute->id)
            ->where('order_index', '<', $minOrderIndex)
            ->where('status', 'active')
            ->exists();

        if ($hasDivisionBefore) {
            return true;
        }

        return StatuteProvision::where('statute_id', $statute->id)
            ->where('order_index', '<', $minOrderIndex)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if there's more content after the returned items
     *
     * @param Statute $statute
     * @param array $items
     * @return bool
     */
    private function hasContentAfter(Statute $statute, array $items): bool
    {
        if (empty($items)) {
            return false;
        }

        $maxOrderIndex = max(array_column($items, 'order_index'));

        $hasDivisionAfter = StatuteDivision::where('statute_id', $statute->id)
            ->where('order_index', '>', $maxOrderIndex)
            ->where('status', 'active')
            ->exists();

        if ($hasDivisionAfter) {
            return true;
        }

        return StatuteProvision::where('statute_id', $statute->id)
            ->where('order_index', '>', $maxOrderIndex)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get total items count for a statute
     *
     * @param Statute $statute
     * @return int
     */
    private function getTotalItems(Statute $statute): int
    {
        $divisionsCount = StatuteDivision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->where('status', 'active')
            ->count();

        $provisionsCount = StatuteProvision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->where('status', 'active')
            ->count();

        return $divisionsCount + $provisionsCount;
    }
}
