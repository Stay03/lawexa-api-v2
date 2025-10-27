<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use App\Services\OrderIndexManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

class StatuteLazyLoadingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Statute $statute;
    private OrderIndexManagerService $orderIndexManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->orderIndexManager = new OrderIndexManagerService();

        // Create test statute with complex hierarchy
        $this->createComplexStatute();
    }

    private function createComplexStatute(): void
    {
        $this->statute = Statute::create([
            'slug' => 'constitution',
            'title' => 'Constitution of Test Country',
            'status' => 'published',
            'enacted_date' => now(),
            'created_by' => $this->user->id,
        ]);

        // Chapter I
        $chapter1 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'chapter-i-fundamental-rights',
            'division_type' => 'chapter',
            'division_number' => 'I',
            'division_title' => 'Fundamental Rights',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // Section 1 under Chapter I
        $section1 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $chapter1->id,
            'slug' => 'section-1-right-to-life',
            'division_type' => 'section',
            'division_number' => '1',
            'division_title' => 'Right to Life',
            'level' => 2,
            'order_index' => 200,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // Provision under Section 1
        StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'division_id' => $section1->id,
            'slug' => 'section-1-subsection-1',
            'provision_type' => 'subsection',
            'provision_number' => '1',
            'provision_text' => 'Every person has the right to life.',
            'level' => 3,
            'order_index' => 300,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // Section 2 under Chapter I
        $section2 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $chapter1->id,
            'slug' => 'section-2-right-to-freedom',
            'division_type' => 'section',
            'division_number' => '2',
            'division_title' => 'Right to Freedom',
            'level' => 2,
            'order_index' => 400,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        // Chapter II
        $chapter2 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'chapter-ii-the-legislature',
            'division_type' => 'chapter',
            'division_number' => 'II',
            'division_title' => 'The Legislature',
            'level' => 1,
            'order_index' => 500,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        // Section 3 under Chapter II
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $chapter2->id,
            'slug' => 'section-3-establishment',
            'division_type' => 'section',
            'division_number' => '3',
            'division_title' => 'Establishment of Parliament',
            'level' => 2,
            'order_index' => 600,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // Chapter III
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'chapter-iii-the-executive',
            'division_type' => 'chapter',
            'division_number' => 'III',
            'division_title' => 'The Executive',
            'level' => 1,
            'order_index' => 700,
            'sort_order' => 3,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_can_perform_complete_hash_first_loading_flow()
    {
        // Scenario: User visits /statutes/constitution#the-legislature

        // Step 1: Lookup the hash target
        $response = $this->getJson("/api/statutes/constitution/content/chapter-ii-the-legislature");

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify we got the target content
        $this->assertEquals('division', $data['type']);
        $this->assertEquals('chapter-ii-the-legislature', $data['content']['slug']);

        // Verify breadcrumb exists
        $this->assertNotNull($data['breadcrumb']);
        $this->assertGreaterThan(1, count($data['breadcrumb']));

        // Verify position metadata
        $this->assertEquals(500, $data['position']['order_index']);
        $this->assertTrue($data['position']['has_content_before']);
        $this->assertTrue($data['position']['has_content_after']);

        // Verify children are loaded
        $this->assertNotNull($data['children']);
        $this->assertGreaterThan(0, count($data['children']));

        return $data['position']['order_index'];
    }

    /**
     * @test
     * @depends it_can_perform_complete_hash_first_loading_flow
     */
    public function it_can_perform_bidirectional_scroll_loading($targetOrderIndex)
    {
        // Step 2: User scrolls up - load content before
        $beforeResponse = $this->getJson(
            "/api/statutes/constitution/content/sequential?from_order={$targetOrderIndex}&direction=before&limit=3"
        );

        $beforeResponse->assertStatus(200);

        $beforeData = $beforeResponse->json('data');
        $this->assertNotEmpty($beforeData['items']);
        $this->assertEquals('before', $beforeData['meta']['direction']);
        $this->assertTrue($beforeData['meta']['has_more']);

        // Verify items are in correct order (descending for before)
        $items = $beforeData['items'];
        for ($i = 0; $i < count($items) - 1; $i++) {
            $this->assertGreaterThan($items[$i + 1]['order_index'], $items[$i]['order_index']);
        }

        // Step 3: User scrolls down - load content after
        $afterResponse = $this->getJson(
            "/api/statutes/constitution/content/sequential?from_order={$targetOrderIndex}&direction=after&limit=3"
        );

        $afterResponse->assertStatus(200);

        $afterData = $afterResponse->json('data');
        $this->assertNotEmpty($afterData['items']);
        $this->assertEquals('after', $afterData['meta']['direction']);

        // Verify items are in correct order (ascending for after)
        $items = $afterData['items'];
        for ($i = 0; $i < count($items) - 1; $i++) {
            $this->assertLessThan($items[$i + 1]['order_index'], $items[$i]['order_index']);
        }
    }

    /** @test */
    public function it_can_load_content_range_for_buffering()
    {
        // Scenario: Frontend wants to prefetch a range of content around the target
        $targetOrderIndex = 500;

        $response = $this->getJson(
            "/api/statutes/constitution/content/range?start_order=400&end_order=600"
        );

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data['items']);

        // Verify all items are within the range
        foreach ($data['items'] as $item) {
            $this->assertGreaterThanOrEqual(400, $item['order_index']);
            $this->assertLessThanOrEqual(600, $item['order_index']);
        }

        // Verify items are sorted
        $orderIndices = array_column($data['items'], 'order_index');
        $sortedIndices = $orderIndices;
        sort($sortedIndices);
        $this->assertEquals($sortedIndices, $orderIndices);
    }

    /** @test */
    public function it_caches_breadcrumbs_for_performance()
    {
        // First request - should cache
        $response1 = $this->getJson("/api/statutes/constitution/content/section-1-right-to-life");
        $response1->assertStatus(200);

        $division = StatuteDivision::where('slug', 'section-1-right-to-life')->first();

        // Verify breadcrumb is cached
        $cacheKey = "breadcrumb:{$this->statute->id}:division:{$division->id}";
        $cached = Cache::tags(["statute:{$this->statute->id}"])->get($cacheKey);

        $this->assertNotNull($cached);
    }

    /** @test */
    public function it_invalidates_caches_when_content_is_updated()
    {
        $division = StatuteDivision::where('slug', 'chapter-i-fundamental-rights')->first();

        // Build and cache breadcrumb
        $response = $this->getJson("/api/statutes/constitution/content/chapter-i-fundamental-rights");
        $response->assertStatus(200);

        // Verify cache exists
        $cacheKey = "breadcrumb:{$this->statute->id}:division:{$division->id}";
        $cached = Cache::tags(["statute:{$this->statute->id}"])->get($cacheKey);
        $this->assertNotNull($cached);

        // Update the division (this should trigger the observer)
        $division->division_title = 'Updated Title';
        $division->save();

        // Cache should be cleared by the observer
        $cached = Cache::tags(["statute:{$this->statute->id}"])->get($cacheKey);
        $this->assertNull($cached);
    }

    /** @test */
    public function it_can_navigate_through_nested_hierarchy()
    {
        // Start at a deeply nested provision
        $response = $this->getJson("/api/statutes/constitution/content/section-1-subsection-1");

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify it's a provision
        $this->assertEquals('provision', $data['type']);

        // Verify breadcrumb shows full path
        $breadcrumb = $data['breadcrumb'];
        $this->assertGreaterThanOrEqual(4, count($breadcrumb)); // Statute -> Chapter -> Section -> Provision

        // Verify breadcrumb order
        $types = array_column($breadcrumb, 'type');
        $this->assertEquals('statute', $types[0]);
        $this->assertEquals('chapter', $types[1]);
        $this->assertEquals('section', $types[2]);
        $this->assertEquals('subsection', $types[3]);
    }

    /** @test */
    public function it_handles_reindexing_workflow()
    {
        // Simulate a scenario where gaps get exhausted
        // Create divisions with small gaps
        $tightDivision1 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'tight-division-1',
            'division_type' => 'chapter',
            'division_number' => '4',
            'division_title' => 'Tight Division 1',
            'level' => 1,
            'order_index' => 800,
            'sort_order' => 4,
            'status' => 'active',
        ]);

        $tightDivision2 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'tight-division-2',
            'division_type' => 'chapter',
            'division_number' => '5',
            'division_title' => 'Tight Division 2',
            'level' => 1,
            'order_index' => 801, // Very small gap
            'sort_order' => 5,
            'status' => 'active',
        ]);

        // Validate and detect the issue
        $validationReport = $this->orderIndexManager->validateIndices($this->statute);

        $this->assertFalse($validationReport['valid']);
        $this->assertStringContainsString('Minimum gap', implode(', ', $validationReport['issues']));

        // Reindex to fix
        $reindexReport = $this->orderIndexManager->reindexStatute($this->statute, false);

        $this->assertGreaterThan(0, $reindexReport['divisions_updated']);

        // Validate again
        $tightDivision1->refresh();
        $tightDivision2->refresh();

        // Verify proper gaps now exist
        $this->assertGreaterThanOrEqual(90, $tightDivision2->order_index - $tightDivision1->order_index);
    }

    /** @test */
    public function it_maintains_performance_with_large_statute()
    {
        // Create a large statute with 50 divisions
        $largeStatute = Statute::create([
            'slug' => 'large-statute',
            'title' => 'Large Test Statute',
            'status' => 'published',
            'enacted_date' => now(),
            'created_by' => $this->user->id,
        ]);

        for ($i = 1; $i <= 50; $i++) {
            StatuteDivision::create([
                'statute_id' => $largeStatute->id,
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

        // Measure lookup performance
        $startTime = microtime(true);
        $response = $this->getJson("/api/statutes/large-statute/content/division-25");
        $endTime = microtime(true);

        $response->assertStatus(200);

        // Verify response is reasonably fast (< 1 second)
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $this->assertLessThan(1000, $duration, "Lookup took {$duration}ms, should be < 1000ms");

        // Measure sequential loading performance
        $startTime = microtime(true);
        $response = $this->getJson(
            "/api/statutes/large-statute/content/sequential?from_order=2500&direction=before&limit=10"
        );
        $endTime = microtime(true);

        $response->assertStatus(200);

        // Verify response is reasonably fast
        $duration = ($endTime - $startTime) * 1000;
        $this->assertLessThan(1000, $duration, "Sequential loading took {$duration}ms, should be < 1000ms");
    }

    /** @test */
    public function it_supports_full_user_journey_from_hash_to_full_statute()
    {
        // Step 1: User lands on #section-1-right-to-life
        $initialResponse = $this->getJson("/api/statutes/constitution/content/section-1-right-to-life");
        $initialResponse->assertStatus(200);

        $initialOrder = $initialResponse->json('data.position.order_index');

        // Step 2: User scrolls up to beginning
        $scrollUpResponse = $this->getJson(
            "/api/statutes/constitution/content/sequential?from_order={$initialOrder}&direction=before&limit=10"
        );
        $scrollUpResponse->assertStatus(200);

        // Step 3: User continues scrolling up until beginning
        $meta = $scrollUpResponse->json('data.meta');
        while ($meta['has_more']) {
            $nextFromOrder = $meta['next_from_order'];
            $nextResponse = $this->getJson(
                "/api/statutes/constitution/content/sequential?from_order={$nextFromOrder}&direction=before&limit=10"
            );
            $nextResponse->assertStatus(200);
            $meta = $nextResponse->json('data.meta');

            // Prevent infinite loop
            if (!$meta['has_more']) {
                break;
            }
        }

        // Verify we reached the beginning
        $this->assertFalse($meta['has_more']);

        // Step 4: User scrolls down past initial position
        $scrollDownResponse = $this->getJson(
            "/api/statutes/constitution/content/sequential?from_order={$initialOrder}&direction=after&limit=10"
        );
        $scrollDownResponse->assertStatus(200);

        // Verify content after exists
        $afterMeta = $scrollDownResponse->json('data.meta');
        $this->assertGreaterThan(0, $afterMeta['returned']);
    }

    /** @test */
    public function it_correctly_handles_position_flags_for_edge_cases()
    {
        // Test first item
        $firstResponse = $this->getJson("/api/statutes/constitution/content/chapter-i-fundamental-rights");
        $firstResponse->assertStatus(200);
        $firstPosition = $firstResponse->json('data.position');

        $this->assertFalse($firstPosition['has_content_before']);
        $this->assertTrue($firstPosition['has_content_after']);

        // Test last item
        $lastResponse = $this->getJson("/api/statutes/constitution/content/chapter-iii-the-executive");
        $lastResponse->assertStatus(200);
        $lastPosition = $lastResponse->json('data.position');

        $this->assertTrue($lastPosition['has_content_before']);
        $this->assertFalse($lastPosition['has_content_after']);

        // Test middle item
        $middleResponse = $this->getJson("/api/statutes/constitution/content/chapter-ii-the-legislature");
        $middleResponse->assertStatus(200);
        $middlePosition = $middleResponse->json('data.position');

        $this->assertTrue($middlePosition['has_content_before']);
        $this->assertTrue($middlePosition['has_content_after']);
    }
}
