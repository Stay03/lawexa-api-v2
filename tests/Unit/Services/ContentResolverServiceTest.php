<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use App\Services\ContentResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContentResolverService $service;
    private Statute $statute;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ContentResolverService();

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
    public function it_can_resolve_division_by_slug()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_title' => 'Test Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $result = $this->service->resolveBySlug($this->statute, 'test-division');

        $this->assertEquals('division', $result['type']);
        $this->assertEquals($division->id, $result['content']->id);
        $this->assertEquals(100, $result['order_index']);
        $this->assertArrayHasKey('position', $result);
    }

    /** @test */
    public function it_can_resolve_provision_by_slug()
    {
        $provision = StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-provision',
            'provision_type' => 'section',
            'provision_number' => '1',
            'provision_text' => 'Test provision text',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $result = $this->service->resolveBySlug($this->statute, 'test-provision');

        $this->assertEquals('provision', $result['type']);
        $this->assertEquals($provision->id, $result['content']->id);
        $this->assertEquals(200, $result['order_index']);
    }

    /** @test */
    public function it_throws_exception_for_non_existent_content()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage("Content with slug 'non-existent' not found");

        $this->service->resolveBySlug($this->statute, 'non-existent');
    }

    /** @test */
    public function it_throws_exception_for_content_without_order_index()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_title' => 'Test Division',
            'level' => 1,
            'order_index' => null, // No order index
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('has no order_index');

        $this->service->resolveBySlug($this->statute, 'test-division');
    }

    /** @test */
    public function it_returns_correct_position_metadata()
    {
        // Create multiple items
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

        $targetDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-2',
            'division_type' => 'chapter',
            'division_title' => 'Division 2',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-3',
            'division_type' => 'chapter',
            'division_title' => 'Division 3',
            'level' => 1,
            'order_index' => 300,
            'sort_order' => 3,
            'status' => 'active',
        ]);

        $result = $this->service->resolveBySlug($this->statute, 'division-2');
        $position = $result['position'];

        $this->assertEquals(200, $position['order_index']);
        $this->assertEquals(3, $position['total_items']);
        $this->assertTrue($position['has_content_before']);
        $this->assertTrue($position['has_content_after']);
    }

    /** @test */
    public function it_returns_false_for_has_content_before_when_first_item()
    {
        $firstDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'first-division',
            'division_type' => 'chapter',
            'division_title' => 'First Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'second-division',
            'division_type' => 'chapter',
            'division_title' => 'Second Division',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $result = $this->service->resolveBySlug($this->statute, 'first-division');
        $position = $result['position'];

        $this->assertFalse($position['has_content_before']);
        $this->assertTrue($position['has_content_after']);
    }

    /** @test */
    public function it_returns_false_for_has_content_after_when_last_item()
    {
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'first-division',
            'division_type' => 'chapter',
            'division_title' => 'First Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $lastDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'last-division',
            'division_type' => 'chapter',
            'division_title' => 'Last Division',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $result = $this->service->resolveBySlug($this->statute, 'last-division');
        $position = $result['position'];

        $this->assertTrue($position['has_content_before']);
        $this->assertFalse($position['has_content_after']);
    }

    /** @test */
    public function it_caches_position_metadata()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_title' => 'Test Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // First call - should cache
        $result1 = $this->service->resolveBySlug($this->statute, 'test-division');

        // Verify cache exists
        $cacheKey = "position:{$this->statute->id}:100";
        $cached = Cache::tags(["statute:{$this->statute->id}"])->get($cacheKey);

        $this->assertNotNull($cached);
        $this->assertEquals(100, $cached['order_index']);
    }

    /** @test */
    public function it_counts_total_items_correctly()
    {
        // Create divisions
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

        // Create provisions
        for ($i = 1; $i <= 3; $i++) {
            StatuteProvision::create([
                'statute_id' => $this->statute->id,
                'slug' => "provision-{$i}",
                'provision_type' => 'section',
                'provision_number' => (string)$i,
                'provision_text' => "Provision {$i}",
                'level' => 1,
                'order_index' => ($i + 5) * 100,
                'sort_order' => $i,
                'status' => 'active',
            ]);
        }

        $totalItems = $this->service->getTotalItems($this->statute);

        $this->assertEquals(8, $totalItems);
    }

    /** @test */
    public function it_can_resolve_by_order_index()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_title' => 'Test Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $result = $this->service->resolveByOrderIndex($this->statute, 100);

        $this->assertNotNull($result);
        $this->assertEquals('division', $result['type']);
        $this->assertEquals($division->id, $result['content']->id);
    }

    /** @test */
    public function it_returns_null_for_non_existent_order_index()
    {
        $result = $this->service->resolveByOrderIndex($this->statute, 9999);

        $this->assertNull($result);
    }

    /** @test */
    public function it_only_returns_active_content()
    {
        // Create an inactive division
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'inactive-division',
            'division_type' => 'chapter',
            'division_title' => 'Inactive Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'inactive',
        ]);

        $this->expectException(NotFoundHttpException::class);

        $this->service->resolveBySlug($this->statute, 'inactive-division');
    }

    /** @test */
    public function it_handles_mixed_divisions_and_provisions_in_total_count()
    {
        // Create divisions
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

        // Create provisions
        StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'provision-1',
            'provision_type' => 'section',
            'provision_number' => '1',
            'provision_text' => 'Provision 1',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // Create inactive items (should not be counted)
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'inactive-division',
            'division_type' => 'chapter',
            'division_title' => 'Inactive Division',
            'level' => 1,
            'order_index' => 300,
            'sort_order' => 2,
            'status' => 'inactive',
        ]);

        $totalItems = $this->service->getTotalItems($this->statute);

        $this->assertEquals(2, $totalItems); // Only active items
    }
}
