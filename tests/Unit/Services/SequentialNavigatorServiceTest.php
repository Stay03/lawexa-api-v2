<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use App\Services\SequentialNavigatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SequentialNavigatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private SequentialNavigatorService $service;
    private Statute $statute;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SequentialNavigatorService();

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

        // Create sequential content
        $this->createSequentialContent();
    }

    private function createSequentialContent(): void
    {
        // Create 10 divisions
        for ($i = 1; $i <= 10; $i++) {
            StatuteDivision::create([
                'statute_id' => $this->statute->id,
                'slug' => "division-{$i}",
                'division_type' => 'chapter',
                'division_number' => (string)$i,
                'division_title' => "Division {$i}",
                'level' => 1,
                'order_index' => $i * 100,
                'sort_order' => $i,
                'status' => 'active',
            ]);
        }
    }

    /** @test */
    public function it_can_load_content_before_position()
    {
        $result = $this->service->loadBefore($this->statute, 500, 3);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(3, $result['items']);

        // Items should be in descending order
        $this->assertEquals(400, $result['items'][0]['order_index']);
        $this->assertEquals(300, $result['items'][1]['order_index']);
        $this->assertEquals(200, $result['items'][2]['order_index']);
    }

    /** @test */
    public function it_can_load_content_after_position()
    {
        $result = $this->service->loadAfter($this->statute, 500, 3);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(3, $result['items']);

        // Items should be in ascending order
        $this->assertEquals(600, $result['items'][0]['order_index']);
        $this->assertEquals(700, $result['items'][1]['order_index']);
        $this->assertEquals(800, $result['items'][2]['order_index']);
    }

    /** @test */
    public function it_respects_limit_parameter()
    {
        $result = $this->service->loadBefore($this->statute, 1000, 2);

        $this->assertCount(2, $result['items']);
        $this->assertEquals(2, $result['meta']['returned']);
    }

    /** @test */
    public function it_enforces_maximum_limit()
    {
        // Try to load 100 items (more than the max of 50)
        $result = $this->service->loadBefore($this->statute, 1000, 100);

        // Should only return up to 50 items (or less if not enough content exists)
        $this->assertLessThanOrEqual(50, count($result['items']));
    }

    /** @test */
    public function it_returns_correct_meta_information()
    {
        $result = $this->service->loadBefore($this->statute, 500, 3);

        $this->assertEquals('before', $result['meta']['direction']);
        $this->assertEquals(500, $result['meta']['from_order']);
        $this->assertEquals(3, $result['meta']['limit']);
        $this->assertEquals(3, $result['meta']['returned']);
        $this->assertArrayHasKey('has_more', $result['meta']);
        $this->assertArrayHasKey('next_from_order', $result['meta']);
    }

    /** @test */
    public function it_returns_has_more_true_when_more_content_exists()
    {
        $result = $this->service->loadBefore($this->statute, 500, 2);

        $this->assertTrue($result['meta']['has_more']);
        $this->assertNotNull($result['meta']['next_from_order']);
    }

    /** @test */
    public function it_returns_has_more_false_at_beginning()
    {
        $result = $this->service->loadBefore($this->statute, 200, 5);

        $this->assertFalse($result['meta']['has_more']);
        $this->assertNull($result['meta']['next_from_order']);
    }

    /** @test */
    public function it_returns_has_more_false_at_end()
    {
        $result = $this->service->loadAfter($this->statute, 900, 5);

        $this->assertFalse($result['meta']['has_more']);
        $this->assertNull($result['meta']['next_from_order']);
    }

    /** @test */
    public function it_can_load_range_of_content()
    {
        $result = $this->service->loadRange($this->statute, 300, 600);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('meta', $result);

        $items = $result['items'];
        $this->assertCount(4, $items); // 300, 400, 500, 600

        // Verify order
        $this->assertEquals(300, $items[0]['order_index']);
        $this->assertEquals(600, $items[3]['order_index']);
    }

    /** @test */
    public function it_validates_range_parameters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('end_order must be greater than or equal to start_order');

        $this->service->loadRange($this->statute, 600, 300);
    }

    /** @test */
    public function it_enforces_maximum_range_size()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Range size cannot exceed 100 items');

        $this->service->loadRange($this->statute, 100, 10200);
    }

    /** @test */
    public function it_handles_mixed_divisions_and_provisions()
    {
        // Add provisions between divisions
        StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'provision-1',
            'provision_type' => 'section',
            'provision_number' => '1',
            'provision_text' => 'Provision 1',
            'level' => 1,
            'order_index' => 250,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'provision-2',
            'provision_type' => 'section',
            'provision_number' => '2',
            'provision_text' => 'Provision 2',
            'level' => 1,
            'order_index' => 350,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $result = $this->service->loadBefore($this->statute, 400, 5);

        $types = array_column($result['items'], 'type');

        // Should contain both divisions and provisions
        $this->assertContains('division', $types);
        $this->assertContains('provision', $types);
    }

    /** @test */
    public function it_returns_correct_content_structure()
    {
        $result = $this->service->loadBefore($this->statute, 500, 1);

        $item = $result['items'][0];

        $this->assertArrayHasKey('order_index', $item);
        $this->assertArrayHasKey('type', $item);
        $this->assertArrayHasKey('content', $item);
        $this->assertArrayHasKey('children', $item);

        $content = $item['content'];
        $this->assertArrayHasKey('id', $content);
        $this->assertArrayHasKey('slug', $content);
        $this->assertArrayHasKey('order_index', $content);
    }

    /** @test */
    public function it_includes_children_by_default_for_divisions()
    {
        // Create a division with children
        $parentDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent-division',
            'division_type' => 'chapter',
            'division_title' => 'Parent',
            'level' => 1,
            'order_index' => 550,
            'sort_order' => 11,
            'status' => 'active',
        ]);

        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $parentDivision->id,
            'slug' => 'child-division',
            'division_type' => 'section',
            'division_title' => 'Child',
            'level' => 2,
            'order_index' => 560,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $result = $this->service->loadBefore($this->statute, 600, 5, true);

        // Find the parent division
        $parentItem = null;
        foreach ($result['items'] as $item) {
            if ($item['order_index'] === 550) {
                $parentItem = $item;
                break;
            }
        }

        $this->assertNotNull($parentItem);
        $this->assertNotEmpty($parentItem['children']);
    }

    /** @test */
    public function it_can_exclude_children()
    {
        // Create a division with children
        $parentDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent-division',
            'division_type' => 'chapter',
            'division_title' => 'Parent',
            'level' => 1,
            'order_index' => 550,
            'sort_order' => 11,
            'status' => 'active',
        ]);

        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $parentDivision->id,
            'slug' => 'child-division',
            'division_type' => 'section',
            'division_title' => 'Child',
            'level' => 2,
            'order_index' => 560,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $result = $this->service->loadBefore($this->statute, 600, 5, false);

        // Find the parent division
        $parentItem = null;
        foreach ($result['items'] as $item) {
            if ($item['order_index'] === 550) {
                $parentItem = $item;
                break;
            }
        }

        $this->assertNotNull($parentItem);
        $this->assertEmpty($parentItem['children']);
    }

    /** @test */
    public function it_returns_empty_array_when_no_content_exists()
    {
        $emptyStatute = Statute::create([
            'slug' => 'empty-statute',
            'title' => 'Empty Statute',
            'status' => 'published',
            'enacted_date' => now(),
            'created_by' => $this->user->id,
        ]);

        $result = $this->service->loadBefore($emptyStatute, 500, 5);

        $this->assertEmpty($result['items']);
        $this->assertEquals(0, $result['meta']['returned']);
        $this->assertFalse($result['meta']['has_more']);
    }

    /** @test */
    public function it_only_returns_active_content()
    {
        // Create inactive divisions
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'inactive-division',
            'division_type' => 'chapter',
            'division_title' => 'Inactive',
            'level' => 1,
            'order_index' => 250,
            'sort_order' => 11,
            'status' => 'inactive',
        ]);

        $result = $this->service->loadBefore($this->statute, 500, 10);

        // Should not include the inactive division
        $orderIndices = array_column($result['items'], 'order_index');
        $this->assertNotContains(250, $orderIndices);
    }

    /** @test */
    public function it_includes_has_children_flag_for_divisions()
    {
        // Create division with children
        $parentDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent',
            'division_type' => 'chapter',
            'division_title' => 'Parent',
            'level' => 1,
            'order_index' => 550,
            'sort_order' => 11,
            'status' => 'active',
        ]);

        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $parentDivision->id,
            'slug' => 'child',
            'division_type' => 'section',
            'division_title' => 'Child',
            'level' => 2,
            'order_index' => 560,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $result = $this->service->loadBefore($this->statute, 600, 5);

        // Find parent in results
        $parentItem = null;
        foreach ($result['items'] as $item) {
            if ($item['order_index'] === 550) {
                $parentItem = $item;
                break;
            }
        }

        $this->assertNotNull($parentItem);
        $this->assertArrayHasKey('has_children', $parentItem['content']);
        $this->assertTrue($parentItem['content']['has_children']);
    }

    /** @test */
    public function it_includes_child_count_for_divisions()
    {
        // Create division with multiple children
        $parentDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent',
            'division_type' => 'chapter',
            'division_title' => 'Parent',
            'level' => 1,
            'order_index' => 550,
            'sort_order' => 11,
            'status' => 'active',
        ]);

        // Create 3 child divisions
        for ($i = 1; $i <= 3; $i++) {
            StatuteDivision::create([
                'statute_id' => $this->statute->id,
                'parent_division_id' => $parentDivision->id,
                'slug' => "child-{$i}",
                'division_type' => 'section',
                'division_title' => "Child {$i}",
                'level' => 2,
                'order_index' => 550 + ($i * 10),
                'sort_order' => $i,
                'status' => 'active',
            ]);
        }

        $result = $this->service->loadBefore($this->statute, 600, 5);

        // Find parent in results
        $parentItem = null;
        foreach ($result['items'] as $item) {
            if ($item['order_index'] === 550) {
                $parentItem = $item;
                break;
            }
        }

        $this->assertNotNull($parentItem);
        $this->assertArrayHasKey('child_count', $parentItem['content']);
        $this->assertEquals(3, $parentItem['content']['child_count']);
    }

    /** @test */
    public function it_returns_correct_next_from_order_for_before_direction()
    {
        $result = $this->service->loadBefore($this->statute, 500, 3);

        // Returned items: 400, 300, 200
        // next_from_order should be the minimum: 200
        $this->assertEquals(200, $result['meta']['next_from_order']);
    }

    /** @test */
    public function it_returns_correct_next_from_order_for_after_direction()
    {
        $result = $this->service->loadAfter($this->statute, 500, 3);

        // Returned items: 600, 700, 800
        // next_from_order should be the maximum: 800
        $this->assertEquals(800, $result['meta']['next_from_order']);
    }

    /** @test */
    public function it_handles_edge_case_at_beginning_of_statute()
    {
        $result = $this->service->loadBefore($this->statute, 100, 5);

        $this->assertEmpty($result['items']);
        $this->assertEquals(0, $result['meta']['returned']);
        $this->assertFalse($result['meta']['has_more']);
        $this->assertNull($result['meta']['next_from_order']);
    }

    /** @test */
    public function it_handles_edge_case_at_end_of_statute()
    {
        $result = $this->service->loadAfter($this->statute, 1000, 5);

        $this->assertEmpty($result['items']);
        $this->assertEquals(0, $result['meta']['returned']);
        $this->assertFalse($result['meta']['has_more']);
        $this->assertNull($result['meta']['next_from_order']);
    }
}
