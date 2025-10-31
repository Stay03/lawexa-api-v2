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
     * @param string $format 'nested' or 'flat'
     * @return array
     */
    public function loadBefore(
        Statute $statute,
        int $fromOrder,
        int $limit = 5,
        bool $includeChildren = true,
        string $format = 'nested'
    ): array {
        $limit = min($limit, self::MAX_LIMIT);

        // Build UNION query for content before the given order
        $items = $this->buildUnionQuery($statute, $fromOrder, 'before', $limit, $includeChildren, $format);

        // Check if there's more content before
        $hasMore = $this->hasContentBefore($statute, $items);

        // Get next from_order for pagination
        $nextFromOrder = !empty($items) ? min(array_column($items, 'order_index')) : null;

        return [
            'items' => $items,
            'meta' => [
                'format' => $format,
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
     * @param string $format 'nested' or 'flat'
     * @return array
     */
    public function loadAfter(
        Statute $statute,
        int $fromOrder,
        int $limit = 5,
        bool $includeChildren = true,
        string $format = 'nested'
    ): array {
        $limit = min($limit, self::MAX_LIMIT);

        // Build UNION query for content after the given order
        $items = $this->buildUnionQuery($statute, $fromOrder, 'after', $limit, $includeChildren, $format);

        // Check if there's more content after
        $hasMore = $this->hasContentAfter($statute, $items);

        // Get next from_order for pagination
        $nextFromOrder = !empty($items) ? max(array_column($items, 'order_index')) : null;

        return [
            'items' => $items,
            'meta' => [
                'format' => $format,
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
     * @param string $format 'nested' or 'flat'
     * @return array
     */
    public function loadRange(
        Statute $statute,
        int $startOrder,
        int $endOrder,
        bool $includeChildren = true,
        string $format = 'nested'
    ): array {
        if ($endOrder < $startOrder) {
            throw new \InvalidArgumentException('end_order must be greater than or equal to start_order');
        }

        // Note: We don't validate the numeric range (endOrder - startOrder) because
        // order indices can be sparse (e.g., 100, 200, 300...). Instead, we validate
        // the actual count of returned items to ensure we don't exceed the limit.

        // Build UNION query for range
        $items = $this->buildRangeQuery($statute, $startOrder, $endOrder, $includeChildren, $format);

        // Validate that we don't return more than 100 actual items
        if (count($items) > 100) {
            throw new \InvalidArgumentException('Range contains more than 100 items. Please use a smaller range.');
        }

        // Get total items in statute
        $totalItems = $this->getTotalItems($statute);

        return [
            'items' => $items,
            'meta' => [
                'format' => $format,
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
     * @param string $format 'nested' or 'flat'
     * @return array
     */
    private function buildUnionQuery(
        Statute $statute,
        int $fromOrder,
        string $direction,
        int $limit,
        bool $includeChildren,
        string $format = 'nested'
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

        // Transform results based on format
        if ($format === 'flat') {
            return $this->transformResultsFlat($results);
        }

        return $this->transformResults($results, $includeChildren);
    }

    /**
     * Build UNION query for range
     *
     * @param Statute $statute
     * @param int $startOrder
     * @param int $endOrder
     * @param bool $includeChildren
     * @param string $format 'nested' or 'flat'
     * @return array
     */
    private function buildRangeQuery(
        Statute $statute,
        int $startOrder,
        int $endOrder,
        bool $includeChildren,
        string $format = 'nested'
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

        // Transform results based on format
        if ($format === 'flat') {
            return $this->transformResultsFlat($results);
        }

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
                'content' => $this->formatContent($result)
            ];

            // Load children if requested - use type-specific keys
            if ($includeChildren) {
                if ($result->content_type === 'division') {
                    $children = $this->loadDivisionChildren($result->id);
                    $item['childDivisions'] = $children['childDivisions'];
                    $item['provisions'] = $children['provisions'];
                } else {
                    // For provisions, load child provisions
                    $children = $this->loadProvisionChildren($result->id);
                    $item['childProvisions'] = $children['childProvisions'];
                }
            } else {
                // Set empty arrays when children not included
                if ($result->content_type === 'division') {
                    $item['childDivisions'] = [];
                    $item['provisions'] = [];
                } else {
                    $item['childProvisions'] = [];
                }
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Transform query results into flat format (no nested children arrays)
     *
     * @param array $results
     * @return array
     */
    private function transformResultsFlat(array $results): array
    {
        $items = [];

        foreach ($results as $result) {
            $item = [
                'order_index' => $result->order_index,
                'type' => $result->content_type,
                'content' => $this->formatContentFlat($result)
            ];

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Format content data for flat format
     *
     * @param object $result
     * @return array
     */
    private function formatContentFlat(object $result): array
    {
        $content = [
            'id' => $result->id,
            'slug' => $result->slug,
            'order_index' => $result->order_index,
            'type_name' => $result->type_name,
            'number' => $result->number,
            'title' => $result->title,
            'level' => $result->level,
            'status' => $result->status,
            'created_at' => $result->created_at,
            'updated_at' => $result->updated_at
        ];

        if ($result->content_type === 'division') {
            $content['subtitle'] = $result->subtitle;
            $content['content'] = $result->content;
            $content['parent_division_id'] = $result->parent_id;
            $content['has_children'] = $this->divisionHasChildren($result->id);
            $content['child_count'] = $this->getDivisionChildCount($result->id);
        } else {
            $content['provision_text'] = $result->provision_text;
            $content['parent_provision_id'] = $result->parent_id;
            $content['parent_division_id'] = $result->division_id;
            $content['has_children'] = $this->provisionHasChildren($result->id);
            $content['child_count'] = $this->getProvisionChildCount($result->id);
        }

        return $content;
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
            'status' => $result->status,
            'created_at' => $result->created_at,
            'updated_at' => $result->updated_at
        ];

        if ($result->content_type === 'division') {
            $content['subtitle'] = $result->subtitle;
            $content['content'] = $result->content;
            $content['parent_division_id'] = $result->parent_id; // Rename to be explicit
            // Calculate has_children and child_count
            $content['has_children'] = $this->divisionHasChildren($result->id);
            $content['child_count'] = $this->getDivisionChildCount($result->id);
        } else {
            $content['provision_text'] = $result->provision_text;
            $content['division_id'] = $result->division_id;
            $content['parent_provision_id'] = $result->parent_id; // Rename to be explicit
            $content['has_children'] = $this->provisionHasChildren($result->id);
        }

        return $content;
    }

    /**
     * Load immediate children for a division (both child divisions and provisions)
     *
     * @param int $divisionId
     * @return array
     */
    private function loadDivisionChildren(int $divisionId): array
    {
        // Load child divisions with parent reference
        $childDivisions = StatuteDivision::where('parent_division_id', $divisionId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->limit(10) // Limit immediate children to prevent large payloads
            ->get([
                'id', 'slug', 'division_type', 'division_number', 'division_title',
                'division_subtitle', 'content', 'parent_division_id', 'level',
                'order_index', 'status'
            ])
            ->map(function ($div) {
                $divArray = $div->toArray();
                // Add has_children flag
                $divArray['has_children'] = $this->divisionHasChildren($div->id);
                return $divArray;
            })
            ->toArray();

        // Load provisions at this division level
        $provisions = StatuteProvision::where('division_id', $divisionId)
            ->where('status', 'active')
            ->whereNull('parent_provision_id') // Only top-level provisions
            ->orderBy('sort_order')
            ->limit(10)
            ->get([
                'id', 'slug', 'provision_type', 'provision_number', 'provision_title',
                'provision_text', 'marginal_note', 'interpretation_note',
                'division_id', 'parent_provision_id', 'level', 'order_index', 'status'
            ])
            ->map(function ($prov) {
                $provArray = $prov->toArray();
                // Add has_children flag
                $provArray['has_children'] = $this->provisionHasChildren($prov->id);
                return $provArray;
            })
            ->toArray();

        // Return separate arrays
        return [
            'childDivisions' => $childDivisions,
            'provisions' => $provisions
        ];
    }

    /**
     * Load child provisions for a provision (subsections, paragraphs, clauses)
     *
     * @param int $provisionId
     * @return array
     */
    private function loadProvisionChildren(int $provisionId): array
    {
        $childProvisions = StatuteProvision::where('parent_provision_id', $provisionId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->limit(20)
            ->get([
                'id', 'slug', 'provision_type', 'provision_number', 'provision_title',
                'provision_text', 'marginal_note', 'interpretation_note',
                'division_id', 'parent_provision_id', 'level', 'order_index', 'status'
            ])
            ->map(function ($prov) {
                $provArray = $prov->toArray();
                // Add has_children flag
                $provArray['has_children'] = $this->provisionHasChildren($prov->id);
                return $provArray;
            })
            ->toArray();

        return [
            'childProvisions' => $childProvisions
        ];
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
     * Get child count for provision
     *
     * @param int $provisionId
     * @return int
     */
    private function getProvisionChildCount(int $provisionId): int
    {
        return StatuteProvision::where('parent_provision_id', $provisionId)
            ->where('status', 'active')
            ->count();
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

    /**
     * Load sequential content in pure format (flat list with all fields at root level)
     * This format is optimized for frontend lazy loading with optional breadcrumbs.
     *
     * @param Statute $statute
     * @param int $fromOrder
     * @param string $direction 'before' or 'after'
     * @param int $limit
     * @param bool $includeBreadcrumb
     * @return array
     */
    public function loadSequentialPure(
        Statute $statute,
        int $fromOrder,
        string $direction,
        int $limit = 15,
        bool $includeBreadcrumb = true
    ): array {
        $limit = min($limit, self::MAX_LIMIT);
        $operator = match($direction) {
            'before' => '<',
            'after' => '>',
            'at' => '>='
        };
        $orderDirection = $direction === 'before' ? 'DESC' : 'ASC';

        // Build UNION query for sequential content
        $query = "
            SELECT * FROM (
                SELECT
                    'division' as content_type,
                    id,
                    slug,
                    order_index,
                    division_type,
                    division_number,
                    division_title,
                    division_subtitle,
                    content,
                    level,
                    parent_division_id,
                    NULL as parent_provision_id,
                    NULL as division_id,
                    NULL as provision_type,
                    NULL as provision_number,
                    NULL as provision_title,
                    NULL as provision_text,
                    NULL as marginal_note,
                    NULL as interpretation_note,
                    status,
                    effective_date,
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
                    NULL as division_type,
                    NULL as division_number,
                    NULL as division_title,
                    NULL as division_subtitle,
                    NULL as content,
                    level,
                    NULL as parent_division_id,
                    parent_provision_id,
                    division_id,
                    provision_type,
                    provision_number,
                    provision_title,
                    provision_text,
                    marginal_note,
                    interpretation_note,
                    status,
                    effective_date,
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

        // Transform to pure format with breadcrumbs
        $items = $this->transformResultsPure($results, $statute, $includeBreadcrumb);

        // Check if there's more content
        $hasMore = $direction === 'before'
            ? $this->hasContentBeforePure($statute, $results)
            : $this->hasContentAfterPure($statute, $results);

        // Get next from_order for pagination
        $nextFromOrder = null;
        if ($hasMore && !empty($results)) {
            $orderIndices = array_map(fn($r) => $r->order_index, $results);
            $nextFromOrder = $direction === 'before' ? min($orderIndices) : max($orderIndices);
        }

        return [
            'items' => $items,
            'meta' => [
                'format' => 'sequential_pure',
                'direction' => $direction,
                'from_order' => $fromOrder,
                'limit' => $limit,
                'returned' => count($items),
                'has_more' => $hasMore,
                'next_from_order' => $nextFromOrder
            ]
        ];
    }

    /**
     * Transform results to pure format with all fields at root level
     *
     * @param array $results
     * @param Statute $statute
     * @param bool $includeBreadcrumb
     * @return array
     */
    private function transformResultsPure(array $results, Statute $statute, bool $includeBreadcrumb): array
    {
        if (empty($results)) {
            return [];
        }

        $items = [];

        // Batch load breadcrumbs efficiently if needed
        $breadcrumbs = [];
        if ($includeBreadcrumb) {
            $breadcrumbs = $this->batchLoadBreadcrumbs($results, $statute);
        }

        foreach ($results as $result) {
            $item = [
                // Identity
                'id' => $result->id,
                'slug' => $result->slug,
                'type' => $result->content_type,

                // Division fields (null if type=provision)
                'division_type' => $result->division_type,
                'division_number' => $result->division_number,
                'division_title' => $result->division_title,
                'division_subtitle' => $result->division_subtitle,
                'content' => $result->content,

                // Provision fields (null if type=division)
                'provision_type' => $result->provision_type,
                'provision_number' => $result->provision_number,
                'provision_title' => $result->provision_title,
                'provision_text' => $result->provision_text,
                'marginal_note' => $result->marginal_note,
                'interpretation_note' => $result->interpretation_note,

                // Hierarchy
                'level' => $result->level,
                'parent_division_id' => $result->parent_division_id,
                'parent_provision_id' => $result->parent_provision_id,

                // Position & children info
                'order_index' => $result->order_index,
                'has_children' => $result->content_type === 'division'
                    ? $this->divisionHasChildren($result->id)
                    : $this->provisionHasChildren($result->id),
                'child_count' => $result->content_type === 'division'
                    ? $this->getDivisionChildCount($result->id)
                    : $this->getProvisionChildCount($result->id),

                // Metadata
                'status' => $result->status,
                'effective_date' => $result->effective_date,
                'created_at' => $result->created_at,
                'updated_at' => $result->updated_at
            ];

            // Add breadcrumb if included
            if ($includeBreadcrumb) {
                $breadcrumbKey = "{$result->content_type}:{$result->id}";
                $item['breadcrumb'] = $breadcrumbs[$breadcrumbKey] ?? [];
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Batch load breadcrumbs for multiple items efficiently
     *
     * @param array $results
     * @param Statute $statute
     * @return array Keyed by "type:id"
     */
    private function batchLoadBreadcrumbs(array $results, Statute $statute): array
    {
        $breadcrumbs = [];

        // Group items by type
        $divisionIds = [];
        $provisionIds = [];

        foreach ($results as $result) {
            if ($result->content_type === 'division') {
                $divisionIds[] = $result->id;
            } else {
                $provisionIds[] = $result->id;
            }
        }

        // Load all divisions needed for breadcrumbs
        $allDivisions = [];
        if (!empty($divisionIds) || !empty($provisionIds)) {
            $divisionIdsToLoad = array_unique(array_merge($divisionIds, $this->getParentDivisionIds($provisionIds)));
            if (!empty($divisionIdsToLoad)) {
                $divisions = StatuteDivision::whereIn('id', $divisionIdsToLoad)
                    ->get(['id', 'slug', 'division_title', 'division_number', 'division_type', 'order_index', 'parent_division_id'])
                    ->keyBy('id');
                $allDivisions = $divisions->toArray();
            }
        }

        // Load all provisions needed for breadcrumbs
        $allProvisions = [];
        if (!empty($provisionIds)) {
            $provisionIdsToLoad = array_unique(array_merge($provisionIds, $this->getParentProvisionIds($provisionIds)));
            $provisions = StatuteProvision::whereIn('id', $provisionIdsToLoad)
                ->get(['id', 'slug', 'provision_title', 'provision_number', 'provision_type', 'order_index', 'parent_provision_id', 'division_id'])
                ->keyBy('id');
            $allProvisions = $provisions->toArray();
        }

        // Build breadcrumbs for each item
        foreach ($results as $result) {
            $breadcrumb = [];

            // Start with statute root
            $breadcrumb[] = [
                'id' => $statute->id,
                'type' => 'statute',
                'slug' => $statute->slug,
                'title' => $statute->title,
                'order_index' => null
            ];

            if ($result->content_type === 'division') {
                // Build division path
                $divisionPath = $this->buildDivisionPathFromArray($result->id, $allDivisions);
                $breadcrumb = array_merge($breadcrumb, $divisionPath);
            } else {
                // Build provision path (includes parent divisions first)
                if ($result->division_id && isset($allDivisions[$result->division_id])) {
                    $divisionPath = $this->buildDivisionPathFromArray($result->division_id, $allDivisions);
                    $breadcrumb = array_merge($breadcrumb, $divisionPath);
                }

                $provisionPath = $this->buildProvisionPathFromArray($result->id, $allProvisions);
                $breadcrumb = array_merge($breadcrumb, $provisionPath);
            }

            $breadcrumbKey = "{$result->content_type}:{$result->id}";
            $breadcrumbs[$breadcrumbKey] = $breadcrumb;
        }

        return $breadcrumbs;
    }

    /**
     * Get all parent division IDs for given provision IDs
     *
     * @param array $provisionIds
     * @return array
     */
    private function getParentDivisionIds(array $provisionIds): array
    {
        if (empty($provisionIds)) {
            return [];
        }

        $provisions = StatuteProvision::whereIn('id', $provisionIds)
            ->whereNotNull('division_id')
            ->pluck('division_id')
            ->unique()
            ->toArray();

        // Recursively get parent divisions
        $allDivisionIds = $provisions;
        $currentLevel = $provisions;

        while (!empty($currentLevel)) {
            $parents = StatuteDivision::whereIn('id', $currentLevel)
                ->whereNotNull('parent_division_id')
                ->pluck('parent_division_id')
                ->unique()
                ->toArray();

            if (empty($parents)) {
                break;
            }

            $allDivisionIds = array_merge($allDivisionIds, $parents);
            $currentLevel = $parents;
        }

        return array_unique($allDivisionIds);
    }

    /**
     * Get all parent provision IDs for given provision IDs
     *
     * @param array $provisionIds
     * @return array
     */
    private function getParentProvisionIds(array $provisionIds): array
    {
        if (empty($provisionIds)) {
            return [];
        }

        $allProvisionIds = [];
        $currentLevel = $provisionIds;

        while (!empty($currentLevel)) {
            $parents = StatuteProvision::whereIn('id', $currentLevel)
                ->whereNotNull('parent_provision_id')
                ->pluck('parent_provision_id')
                ->unique()
                ->toArray();

            if (empty($parents)) {
                break;
            }

            $allProvisionIds = array_merge($allProvisionIds, $parents);
            $currentLevel = $parents;
        }

        return array_unique($allProvisionIds);
    }

    /**
     * Build division path from preloaded array
     *
     * @param int $divisionId
     * @param array $allDivisions
     * @return array
     */
    private function buildDivisionPathFromArray(int $divisionId, array $allDivisions): array
    {
        $path = [];
        $currentId = $divisionId;

        while ($currentId && isset($allDivisions[$currentId])) {
            $division = $allDivisions[$currentId];
            array_unshift($path, [
                'id' => $division['id'],
                'type' => 'division',
                'slug' => $division['slug'],
                'division_type' => $division['division_type'],
                'division_number' => $division['division_number'],
                'division_title' => $division['division_title'],
                'level' => $division['level'] ?? null,
                'order_index' => $division['order_index']
            ]);

            $currentId = $division['parent_division_id'] ?? null;
        }

        return $path;
    }

    /**
     * Build provision path from preloaded array
     *
     * @param int $provisionId
     * @param array $allProvisions
     * @return array
     */
    private function buildProvisionPathFromArray(int $provisionId, array $allProvisions): array
    {
        $path = [];
        $currentId = $provisionId;

        while ($currentId && isset($allProvisions[$currentId])) {
            $provision = $allProvisions[$currentId];
            array_unshift($path, [
                'id' => $provision['id'],
                'type' => 'provision',
                'slug' => $provision['slug'],
                'provision_type' => $provision['provision_type'],
                'provision_number' => $provision['provision_number'],
                'provision_title' => $provision['provision_title'],
                'level' => $provision['level'] ?? null,
                'order_index' => $provision['order_index']
            ]);

            $currentId = $provision['parent_provision_id'] ?? null;
        }

        return $path;
    }

    /**
     * Check if there's more content before (for pure format)
     *
     * @param Statute $statute
     * @param array $results
     * @return bool
     */
    private function hasContentBeforePure(Statute $statute, array $results): bool
    {
        if (empty($results)) {
            return false;
        }

        $minOrderIndex = min(array_map(fn($r) => $r->order_index, $results));

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
     * Check if there's more content after (for pure format)
     *
     * @param Statute $statute
     * @param array $results
     * @return bool
     */
    private function hasContentAfterPure(Statute $statute, array $results): bool
    {
        if (empty($results)) {
            return false;
        }

        $maxOrderIndex = max(array_map(fn($r) => $r->order_index, $results));

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
}
