<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class StatuteRangeLoadingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Statute $statute;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        // Create test statute
        $this->statute = Statute::create([
            'slug' => 'test-statute',
            'title' => 'Test Statute',
            'status' => 'published',
            'enacted_date' => now(),
            'created_by' => $this->user->id,
        ]);

        // Create a sequence of content items
        $this->createSequentialContent();
    }

    private function createSequentialContent(): void
    {
        // Create 15 divisions with order indices
        for ($i = 1; $i <= 15; $i++) {
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
    public function it_can_load_content_range()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=300&end_order=600"
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'items' => [
                        '*' => [
                            'order_index',
                            'type',
                            'content',
                        ],
                    ],
                    'meta' => [
                        'start_order',
                        'end_order',
                        'returned',
                        'total_items_in_statute',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'meta' => [
                        'start_order' => 300,
                        'end_order' => 600,
                    ],
                ],
            ]);

        $items = $response->json('data.items');
        $this->assertCount(4, $items); // 300, 400, 500, 600

        // Verify items are in ascending order
        $this->assertEquals(300, $items[0]['order_index']);
        $this->assertEquals(400, $items[1]['order_index']);
        $this->assertEquals(500, $items[2]['order_index']);
        $this->assertEquals(600, $items[3]['order_index']);
    }

    /** @test */
    public function it_returns_items_in_ascending_order()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=200&end_order=800"
        );

        $response->assertStatus(200);

        $items = $response->json('data.items');
        $orderIndices = array_column($items, 'order_index');

        // Check that array is sorted in ascending order
        $sortedIndices = $orderIndices;
        sort($sortedIndices);
        $this->assertEquals($sortedIndices, $orderIndices);
    }

    /** @test */
    public function it_includes_total_items_in_meta()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=300&end_order=600"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'meta' => [
                        'total_items_in_statute' => 15,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_validates_required_parameters()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range"
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_order', 'end_order']);
    }

    /** @test */
    public function it_validates_end_order_greater_than_or_equal_to_start_order()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=600&end_order=300"
        );

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    /** @test */
    public function it_validates_start_order_is_positive()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=-1&end_order=500"
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_order']);
    }

    /** @test */
    public function it_enforces_maximum_range_size_of_100()
    {
        // Create 101 more divisions to exceed the 100 item limit
        for ($i = 16; $i <= 116; $i++) {
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

        // Request range that would return 101 items (16*100 to 116*100)
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=1600&end_order=11600"
        );

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    /** @test */
    public function it_can_load_single_item_range()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=500&end_order=500"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'meta' => [
                        'returned' => 1,
                    ],
                ],
            ]);

        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        $this->assertEquals(500, $items[0]['order_index']);
    }

    /** @test */
    public function it_returns_empty_array_for_range_with_no_content()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=5000&end_order=6000"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'items' => [],
                    'meta' => [
                        'returned' => 0,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_handles_mixed_divisions_and_provisions()
    {
        // Add provisions interspersed with divisions
        StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'provision-1',
            'provision_type' => 'section',
            'provision_number' => '1',
            'provision_text' => 'Test provision 1',
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
            'provision_text' => 'Test provision 2',
            'level' => 1,
            'order_index' => 350,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=200&end_order=400"
        );

        $response->assertStatus(200);

        $items = $response->json('data.items');
        $types = array_column($items, 'type');

        // Should contain both divisions and provisions
        $this->assertContains('division', $types);
        $this->assertContains('provision', $types);

        // Verify they're ordered correctly
        $orderIndices = array_column($items, 'order_index');
        $sortedIndices = $orderIndices;
        sort($sortedIndices);
        $this->assertEquals($sortedIndices, $orderIndices);
    }

    /** @test */
    public function it_can_exclude_children()
    {
        // Create a division with children
        $parentDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent-division',
            'division_type' => 'chapter',
            'division_number' => '16',
            'division_title' => 'Parent Division',
            'level' => 1,
            'order_index' => 550,
            'sort_order' => 16,
            'status' => 'active',
        ]);

        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $parentDivision->id,
            'slug' => 'child-division',
            'division_type' => 'section',
            'division_number' => '1',
            'division_title' => 'Child Division',
            'level' => 2,
            'order_index' => 560,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=500&end_order=600&include_children=false"
        );

        $response->assertStatus(200);

        // Check that children array is empty or not loaded for the parent division
        $items = $response->json('data.items');
        foreach ($items as $item) {
            if ($item['order_index'] === 550) {
                $this->assertEmpty($item['children']);
            }
        }
    }

    /** @test */
    public function it_includes_children_by_default()
    {
        // Create a division with children
        $parentDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent-division',
            'division_type' => 'chapter',
            'division_number' => '16',
            'division_title' => 'Parent Division',
            'level' => 1,
            'order_index' => 550,
            'sort_order' => 16,
            'status' => 'active',
        ]);

        $childDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $parentDivision->id,
            'slug' => 'child-division',
            'division_type' => 'section',
            'division_number' => '1',
            'division_title' => 'Child Division',
            'level' => 2,
            'order_index' => 560,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=500&end_order=600"
        );

        $response->assertStatus(200);

        // Find the parent division in items and check children
        $items = $response->json('data.items');
        $parentItem = null;
        foreach ($items as $item) {
            if ($item['order_index'] === 550) {
                $parentItem = $item;
                break;
            }
        }

        $this->assertNotNull($parentItem);
        $this->assertNotEmpty($parentItem['children']);
    }

    /** @test */
    public function it_returns_correct_returned_count()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=300&end_order=700"
        );

        $response->assertStatus(200);

        $meta = $response->json('data.meta');
        $items = $response->json('data.items');

        $this->assertEquals(count($items), $meta['returned']);
        $this->assertEquals(5, $meta['returned']); // 300, 400, 500, 600, 700
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Create a fresh application instance without authentication
        $this->refreshApplication();

        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/range?start_order=300&end_order=600"
        );

        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_404_for_non_existent_statute()
    {
        $response = $this->getJson(
            "/api/statutes/non-existent-statute/content/range?start_order=300&end_order=600"
        );

        $response->assertStatus(404);
    }
}
