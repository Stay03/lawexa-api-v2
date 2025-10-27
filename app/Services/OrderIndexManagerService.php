<?php

namespace App\Services;

use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Order Index Manager Service
 *
 * Manages the order_index values for statute divisions and provisions.
 * Uses a gap-based system to enable efficient insertions without cascading updates.
 */
class OrderIndexManagerService
{
    /**
     * Gap size between order indices
     */
    private const GAP_SIZE = 100;

    /**
     * Minimum gap threshold before triggering reindexing
     */
    private const MIN_GAP_THRESHOLD = 2;

    /**
     * Calculate order index for new content
     *
     * @param Statute $statute
     * @param string $type 'division' or 'provision'
     * @param int|null $parentId
     * @param int|null $afterOrderIndex Insert after this index (null for end)
     * @return int
     */
    public function calculateOrderIndex(
        Statute $statute,
        string $type,
        ?int $parentId = null,
        ?int $afterOrderIndex = null
    ): int {
        // If inserting after a specific position
        if ($afterOrderIndex !== null) {
            return $this->calculateInsertionIndex($statute, $afterOrderIndex);
        }

        // Get the last order_index for this statute
        $lastIndex = $this->getLastOrderIndex($statute);

        // Return next available index with gap
        return $lastIndex + self::GAP_SIZE;
    }

    /**
     * Calculate insertion index between two existing indices
     *
     * @param Statute $statute
     * @param int $afterIndex
     * @return int
     */
    private function calculateInsertionIndex(Statute $statute, int $afterIndex): int
    {
        // Find the next index after the given position
        $nextIndex = $this->getNextOrderIndex($statute, $afterIndex);

        if ($nextIndex === null) {
            // No content after, append with gap
            return $afterIndex + self::GAP_SIZE;
        }

        $gap = $nextIndex - $afterIndex;

        // If gap is too small, trigger reindexing
        if ($gap < self::MIN_GAP_THRESHOLD) {
            $this->reindexStatute($statute);
            // Recalculate after reindexing
            return $this->calculateInsertionIndex($statute, $afterIndex);
        }

        // Use midpoint
        return $afterIndex + intval($gap / 2);
    }

    /**
     * Get the last order_index for a statute
     *
     * @param Statute $statute
     * @return int
     */
    private function getLastOrderIndex(Statute $statute): int
    {
        $lastDivision = StatuteDivision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->orderBy('order_index', 'desc')
            ->value('order_index');

        $lastProvision = StatuteProvision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->orderBy('order_index', 'desc')
            ->value('order_index');

        return max($lastDivision ?? 0, $lastProvision ?? 0);
    }

    /**
     * Get the next order_index after a given index
     *
     * @param Statute $statute
     * @param int $afterIndex
     * @return int|null
     */
    private function getNextOrderIndex(Statute $statute, int $afterIndex): ?int
    {
        $nextDivision = StatuteDivision::where('statute_id', $statute->id)
            ->where('order_index', '>', $afterIndex)
            ->orderBy('order_index', 'asc')
            ->value('order_index');

        $nextProvision = StatuteProvision::where('statute_id', $statute->id)
            ->where('order_index', '>', $afterIndex)
            ->orderBy('order_index', 'asc')
            ->value('order_index');

        if ($nextDivision === null && $nextProvision === null) {
            return null;
        }

        if ($nextDivision === null) {
            return $nextProvision;
        }

        if ($nextProvision === null) {
            return $nextDivision;
        }

        return min($nextDivision, $nextProvision);
    }

    /**
     * Reindex all content for a statute
     *
     * This recalculates order_index values for all divisions and provisions,
     * maintaining the correct reading order with proper gaps.
     *
     * @param Statute $statute
     * @param bool $dryRun If true, returns report without making changes
     * @return array Report of changes
     */
    public function reindexStatute(Statute $statute, bool $dryRun = false): array
    {
        $startTime = microtime(true);
        $report = [
            'statute_id' => $statute->id,
            'statute_slug' => $statute->slug,
            'total_items' => 0,
            'divisions_updated' => 0,
            'provisions_updated' => 0,
            'dry_run' => $dryRun,
            'duration_ms' => 0,
            'changes' => []
        ];

        DB::beginTransaction();

        try {
            // Collect all content ordered by hierarchy
            $orderedContent = $this->collectOrderedContent($statute);
            $report['total_items'] = count($orderedContent);

            $currentIndex = self::GAP_SIZE;
            $updates = ['divisions' => [], 'provisions' => []];

            // Assign new indices
            foreach ($orderedContent as $item) {
                $oldIndex = $item['order_index'];
                $newIndex = $currentIndex;

                $change = [
                    'type' => $item['type'],
                    'id' => $item['id'],
                    'slug' => $item['slug'],
                    'old_index' => $oldIndex,
                    'new_index' => $newIndex
                ];

                $report['changes'][] = $change;

                if (!$dryRun) {
                    $updates[$item['type'] . 's'][$item['id']] = $newIndex;
                }

                $currentIndex += self::GAP_SIZE;
            }

            // Batch update divisions
            if (!$dryRun && !empty($updates['divisions'])) {
                foreach ($updates['divisions'] as $id => $orderIndex) {
                    StatuteDivision::where('id', $id)->update(['order_index' => $orderIndex]);
                    $report['divisions_updated']++;
                }
            }

            // Batch update provisions
            if (!$dryRun && !empty($updates['provisions'])) {
                foreach ($updates['provisions'] as $id => $orderIndex) {
                    StatuteProvision::where('id', $id)->update(['order_index' => $orderIndex]);
                    $report['provisions_updated']++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
                // Clear cache for this statute
                if (config('statute.cache.tags_enabled', false)) {
                    Cache::tags(["statute:{$statute->id}"])->flush();
                } else {
                    // Without tags, clear entire cache or skip
                    // For testing, we'll skip cache invalidation
                    Cache::flush();
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $report['error'] = $e->getMessage();
        }

        $report['duration_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        return $report;
    }

    /**
     * Collect all content in reading order
     *
     * @param Statute $statute
     * @return array
     */
    private function collectOrderedContent(Statute $statute): array
    {
        $content = [];

        // Get top-level divisions
        $topLevelDivisions = StatuteDivision::where('statute_id', $statute->id)
            ->whereNull('parent_division_id')
            ->orderBy('sort_order')
            ->get();

        foreach ($topLevelDivisions as $division) {
            $this->collectDivisionHierarchy($division, $content);
        }

        return $content;
    }

    /**
     * Recursively collect division and its children in reading order
     *
     * @param StatuteDivision $division
     * @param array &$content
     * @return void
     */
    private function collectDivisionHierarchy(StatuteDivision $division, array &$content): void
    {
        // Add the division itself
        $content[] = [
            'type' => 'division',
            'id' => $division->id,
            'slug' => $division->slug,
            'order_index' => $division->order_index,
            'sort_order' => $division->sort_order
        ];

        // Add provisions for this division
        $provisions = StatuteProvision::where('division_id', $division->id)
            ->whereNull('parent_provision_id')
            ->orderBy('sort_order')
            ->get();

        foreach ($provisions as $provision) {
            $this->collectProvisionHierarchy($provision, $content);
        }

        // Add child divisions
        $childDivisions = StatuteDivision::where('parent_division_id', $division->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($childDivisions as $childDivision) {
            $this->collectDivisionHierarchy($childDivision, $content);
        }
    }

    /**
     * Recursively collect provision and its children
     *
     * @param StatuteProvision $provision
     * @param array &$content
     * @return void
     */
    private function collectProvisionHierarchy(StatuteProvision $provision, array &$content): void
    {
        // Add the provision itself
        $content[] = [
            'type' => 'provision',
            'id' => $provision->id,
            'slug' => $provision->slug,
            'order_index' => $provision->order_index,
            'sort_order' => $provision->sort_order
        ];

        // Add child provisions
        $childProvisions = StatuteProvision::where('parent_provision_id', $provision->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($childProvisions as $childProvision) {
            $this->collectProvisionHierarchy($childProvision, $content);
        }
    }

    /**
     * Validate indices for a statute
     *
     * @param Statute $statute
     * @return array Validation report
     */
    public function validateIndices(Statute $statute): array
    {
        $report = [
            'statute_id' => $statute->id,
            'statute_slug' => $statute->slug,
            'valid' => true,
            'issues' => [],
            'statistics' => []
        ];

        // Count total items
        $totalDivisions = StatuteDivision::where('statute_id', $statute->id)->count();
        $totalProvisions = StatuteProvision::where('statute_id', $statute->id)->count();
        $totalItems = $totalDivisions + $totalProvisions;

        // Count items with order_index
        $divisionsWithIndex = StatuteDivision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->count();
        $provisionsWithIndex = StatuteProvision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->count();

        $report['statistics'] = [
            'total_items' => $totalItems,
            'items_with_index' => $divisionsWithIndex + $provisionsWithIndex,
            'items_without_index' => $totalItems - ($divisionsWithIndex + $provisionsWithIndex)
        ];

        // Check for missing indices
        if ($report['statistics']['items_without_index'] > 0) {
            $report['valid'] = false;
            $report['issues'][] = "{$report['statistics']['items_without_index']} items missing order_index";
        }

        // Check for duplicate indices
        $duplicates = $this->findDuplicateIndices($statute);
        if (!empty($duplicates)) {
            $report['valid'] = false;
            $report['issues'][] = count($duplicates) . " duplicate order_index values found";
            $report['duplicates'] = $duplicates;
        }

        // Calculate average gap size
        $gaps = $this->calculateGaps($statute);
        if (!empty($gaps)) {
            $avgGap = array_sum($gaps) / count($gaps);
            $minGap = min($gaps);

            $report['statistics']['average_gap'] = round($avgGap, 2);
            $report['statistics']['minimum_gap'] = $minGap;

            if ($minGap < self::MIN_GAP_THRESHOLD) {
                $report['valid'] = false;
                $report['issues'][] = "Minimum gap ($minGap) below threshold (" . self::MIN_GAP_THRESHOLD . ")";
                $report['recommendation'] = 'Reindexing recommended';
            }
        }

        if ($report['valid']) {
            $report['recommendation'] = 'No issues found';
        }

        return $report;
    }

    /**
     * Find duplicate order_index values
     *
     * @param Statute $statute
     * @return array
     */
    private function findDuplicateIndices(Statute $statute): array
    {
        $allIndices = [];

        // Collect all indices from divisions
        $divisionIndices = StatuteDivision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->pluck('order_index', 'id')
            ->toArray();

        // Collect all indices from provisions
        $provisionIndices = StatuteProvision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->pluck('order_index', 'id')
            ->toArray();

        // Combine and find duplicates
        $allIndices = array_merge($divisionIndices, $provisionIndices);
        $valueCounts = array_count_values($allIndices);

        return array_filter($valueCounts, fn($count) => $count > 1);
    }

    /**
     * Calculate gaps between consecutive indices
     *
     * @param Statute $statute
     * @return array
     */
    private function calculateGaps(Statute $statute): array
    {
        // Get all indices sorted
        $divisionIndices = StatuteDivision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->pluck('order_index')
            ->toArray();

        $provisionIndices = StatuteProvision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->pluck('order_index')
            ->toArray();

        $allIndices = array_merge($divisionIndices, $provisionIndices);
        sort($allIndices);

        $gaps = [];
        for ($i = 1; $i < count($allIndices); $i++) {
            $gaps[] = $allIndices[$i] - $allIndices[$i - 1];
        }

        return $gaps;
    }

    /**
     * Get total items count for a statute
     *
     * @param Statute $statute
     * @return int
     */
    public function getTotalItems(Statute $statute): int
    {
        $divisionsCount = StatuteDivision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->count();

        $provisionsCount = StatuteProvision::where('statute_id', $statute->id)
            ->whereNotNull('order_index')
            ->count();

        return $divisionsCount + $provisionsCount;
    }
}
