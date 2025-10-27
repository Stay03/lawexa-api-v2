<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use App\Services\OrderIndexManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class OrderIndexManagerServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderIndexManagerService $service;
    private Statute $statute;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new OrderIndexManagerService();

        // Create user for statute
        $this->user = User::factory()->create();

        // Create test statute
        $this->statute = Statute::create([
            'slug' => 'test-statute',
            'title' => 'Test Statute',
            'status' => 'published',
            'enacted_date' => now(),
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_calculate_order_index_for_first_item()
    {
        $orderIndex = $this->service->calculateOrderIndex($this->statute, 'division');

        // First item should be at gap size (100)
        $this->assertEquals(100, $orderIndex);
    }

    /** @test */
    public function it_can_calculate_order_index_for_subsequent_items()
    {
        // Create first division
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
            'division_title' => 'Division 1',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $orderIndex = $this->service->calculateOrderIndex($this->statute, 'division');

        // Second item should be at 200 (100 + gap_size)
        $this->assertEquals(200, $orderIndex);
    }

    /** @test */
    public function it_can_calculate_insertion_index_with_sufficient_gap()
    {
        // Create two divisions with a large gap
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
            'division_title' => 'Division 1',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-2',
            'division_type' => 'chapter',
            'division_title' => 'Division 2',
            'level' => 1,
            'order_index' => 300,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        // Insert between 100 and 300
        $orderIndex = $this->service->calculateOrderIndex($this->statute, 'division', null, 100);

        // Should use midpoint: (100 + 300) / 2 = 200
        $this->assertEquals(200, $orderIndex);
    }

    /** @test */
    public function it_can_reindex_entire_statute()
    {
        // Create divisions with messy order indices
        $division1 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
            'division_title' => 'Division 1',
            'level' => 1,
            'order_index' => 50,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $division2 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-2',
            'division_type' => 'chapter',
            'division_title' => 'Division 2',
            'level' => 1,
            'order_index' => 51,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $division3 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-3',
            'division_type' => 'chapter',
            'division_title' => 'Division 3',
            'level' => 1,
            'order_index' => 52,
            'sort_order' => 3,
            'status' => 'active',
        ]);

        $report = $this->service->reindexStatute($this->statute, false);

        // Refresh models to get new values
        $division1->refresh();
        $division2->refresh();
        $division3->refresh();

        // Check report
        $this->assertEquals(3, $report['total_items']);
        $this->assertEquals(3, $report['divisions_updated']);
        $this->assertFalse($report['dry_run']);

        // Check new indices have proper gaps
        $this->assertEquals(100, $division1->order_index);
        $this->assertEquals(200, $division2->order_index);
        $this->assertEquals(300, $division3->order_index);
    }

    /** @test */
    public function it_can_do_dry_run_reindex_without_making_changes()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
            'division_title' => 'Division 1',
            'level' => 1,
            'order_index' => 50,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $originalIndex = $division->order_index;

        $report = $this->service->reindexStatute($this->statute, true);

        $division->refresh();

        // Check report shows dry run
        $this->assertTrue($report['dry_run']);
        $this->assertEquals(1, $report['total_items']);

        // Check that order_index was not actually changed
        $this->assertEquals($originalIndex, $division->order_index);

        // But report should show what would change
        $this->assertEquals(100, $report['changes'][0]['new_index']);
    }

    /** @test */
    public function it_can_validate_statute_indices()
    {
        // Create divisions with valid indices
        for ($i = 1; $i <= 3; $i++) {
            StatuteDivision::create([
                'statute_id' => $this->statute->id,
                'slug' => "division-{$i}",
                'division_type' => 'chapter',
                'division_title' => "Division {$i}",
                'level' => 1,
                'order_index' => $i * 100,
                'sort_order' => $i,
                'status' => 'active',
            ]);
        }

        $report = $this->service->validateIndices($this->statute);

        $this->assertTrue($report['valid']);
        $this->assertEmpty($report['issues']);
        $this->assertEquals(3, $report['statistics']['total_items']);
        $this->assertEquals(3, $report['statistics']['items_with_index']);
        $this->assertEquals(0, $report['statistics']['items_without_index']);
    }

    /** @test */
    public function it_detects_missing_indices()
    {
        // Create division without order_index
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
            'division_title' => 'Division 1',
            'level' => 1,
            'order_index' => null,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-2',
            'division_type' => 'chapter',
            'division_title' => 'Division 2',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $report = $this->service->validateIndices($this->statute);

        $this->assertFalse($report['valid']);
        $this->assertNotEmpty($report['issues']);
        $this->assertEquals(1, $report['statistics']['items_without_index']);
        $this->assertStringContainsString('missing order_index', $report['issues'][0]);
    }

    /** @test */
    public function it_detects_duplicate_indices()
    {
        // Create divisions with duplicate order_index
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
            'division_title' => 'Division 1',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-2',
            'division_type' => 'chapter',
            'division_title' => 'Division 2',
            'level' => 1,
            'order_index' => 100, // Duplicate!
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $report = $this->service->validateIndices($this->statute);

        $this->assertFalse($report['valid']);
        $this->assertArrayHasKey('duplicates', $report);
        $this->assertArrayHasKey(100, $report['duplicates']);
        $this->assertEquals(2, $report['duplicates'][100]);
    }

    /** @test */
    public function it_detects_insufficient_gaps()
    {
        // Create divisions with very small gaps
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
            'division_title' => 'Division 1',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-2',
            'division_type' => 'chapter',
            'division_title' => 'Division 2',
            'level' => 1,
            'order_index' => 101, // Gap of only 1
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $report = $this->service->validateIndices($this->statute);

        $this->assertFalse($report['valid']);
        $this->assertEquals(1, $report['statistics']['minimum_gap']);
        $this->assertStringContainsString('Minimum gap', $report['issues'][0]);
        $this->assertEquals('Reindexing recommended', $report['recommendation']);
    }

    /** @test */
    public function it_calculates_average_gap_size()
    {
        // Create divisions with uniform gaps
        for ($i = 1; $i <= 5; $i++) {
            StatuteDivision::create([
                'statute_id' => $this->statute->id,
                'slug' => "division-{$i}",
                'division_type' => 'chapter',
                'division_title' => "Division {$i}",
                'level' => 1,
                'order_index' => $i * 100,
                'sort_order' => $i,
                'status' => 'active',
            ]);
        }

        $report = $this->service->validateIndices($this->statute);

        $this->assertEquals(100, $report['statistics']['average_gap']);
    }

    /** @test */
    public function it_clears_cache_after_reindexing()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
            'division_title' => 'Division 1',
            'level' => 1,
            'order_index' => 50,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // Cache something for this statute
        Cache::tags(["statute:{$this->statute->id}"])->put('test_key', 'test_value', 3600);

        // Verify cache exists
        $this->assertNotNull(Cache::tags(["statute:{$this->statute->id}"])->get('test_key'));

        // Reindex
        $this->service->reindexStatute($this->statute, false);

        // Cache should be cleared
        $this->assertNull(Cache::tags(["statute:{$this->statute->id}"])->get('test_key'));
    }

    /** @test */
    public function it_handles_mixed_divisions_and_provisions_in_reindexing()
    {
        // Create parent division
        $parentDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent',
            'division_type' => 'chapter',
            'division_title' => 'Parent',
            'level' => 1,
            'order_index' => 10,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // Create provision under parent
        $provision = StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'division_id' => $parentDivision->id,
            'slug' => 'provision-1',
            'provision_type' => 'section',
            'provision_number' => '1',
            'provision_text' => 'Provision 1',
            'level' => 2,
            'order_index' => 11,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // Create child division
        $childDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $parentDivision->id,
            'slug' => 'child',
            'division_type' => 'section',
            'division_title' => 'Child',
            'level' => 2,
            'order_index' => 12,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $report = $this->service->reindexStatute($this->statute, false);

        $parentDivision->refresh();
        $provision->refresh();
        $childDivision->refresh();

        // Check report
        $this->assertEquals(3, $report['total_items']);
        $this->assertEquals(2, $report['divisions_updated']);
        $this->assertEquals(1, $report['provisions_updated']);

        // Check proper ordering: parent -> provision -> child
        $this->assertEquals(100, $parentDivision->order_index);
        $this->assertEquals(200, $provision->order_index);
        $this->assertEquals(300, $childDivision->order_index);
    }

    /** @test */
    public function it_maintains_hierarchy_order_in_reindexing()
    {
        // Create complex hierarchy
        $chapter = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'chapter-1',
            'division_type' => 'chapter',
            'division_title' => 'Chapter 1',
            'level' => 1,
            'order_index' => 1,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $section = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $chapter->id,
            'slug' => 'section-1',
            'division_type' => 'section',
            'division_title' => 'Section 1',
            'level' => 2,
            'order_index' => 2,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $anotherChapter = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'chapter-2',
            'division_type' => 'chapter',
            'division_title' => 'Chapter 2',
            'level' => 1,
            'order_index' => 3,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $this->service->reindexStatute($this->statute, false);

        $chapter->refresh();
        $section->refresh();
        $anotherChapter->refresh();

        // Verify proper order: Chapter1 < Section1 < Chapter2
        $this->assertLessThan($section->order_index, $chapter->order_index);
        $this->assertLessThan($anotherChapter->order_index, $section->order_index);
    }

    /** @test */
    public function it_returns_total_items_count()
    {
        // Create divisions
        for ($i = 1; $i <= 3; $i++) {
            StatuteDivision::create([
                'statute_id' => $this->statute->id,
                'slug' => "division-{$i}",
                'division_type' => 'chapter',
                'division_title' => "Division {$i}",
                'level' => 1,
                'order_index' => $i * 100,
                'sort_order' => $i,
                'status' => 'active',
            ]);
        }

        // Create provisions
        for ($i = 1; $i <= 2; $i++) {
            StatuteProvision::create([
                'statute_id' => $this->statute->id,
                'slug' => "provision-{$i}",
                'provision_type' => 'section',
                'provision_number' => (string)$i,
                'provision_text' => "Provision {$i}",
                'level' => 1,
                'order_index' => ($i + 3) * 100,
                'sort_order' => $i,
                'status' => 'active',
            ]);
        }

        $totalItems = $this->service->getTotalItems($this->statute);

        $this->assertEquals(5, $totalItems);
    }

    /** @test */
    public function it_includes_duration_in_reindex_report()
    {
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
            'division_title' => 'Division 1',
            'level' => 1,
            'order_index' => 50,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $report = $this->service->reindexStatute($this->statute, false);

        $this->assertArrayHasKey('duration_ms', $report);
        $this->assertIsNumeric($report['duration_ms']);
        $this->assertGreaterThan(0, $report['duration_ms']);
    }
}
