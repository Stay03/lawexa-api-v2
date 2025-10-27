<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class StatuteSequentialNavigationTest extends TestCase
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
        // Create 10 divisions with order indices
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
    public function it_can_load_content_before_a_position()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=500&direction=before&limit=3"
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
                        'direction',
                        'from_order',
                        'limit',
                        'returned',
                        'has_more',
                        'next_from_order',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'meta' => [
                        'direction' => 'before',
                        'from_order' => 500,
                        'returned' => 3,
                    ],
                ],
            ]);

        $items = $response->json('data.items');
        $this->assertCount(3, $items);

        // Items should be in descending order (most recent first)
        $this->assertEquals(400, $items[0]['order_index']);
        $this->assertEquals(300, $items[1]['order_index']);
        $this->assertEquals(200, $items[2]['order_index']);
    }

    /** @test */
    public function it_can_load_content_after_a_position()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=500&direction=after&limit=3"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'meta' => [
                        'direction' => 'after',
                        'from_order' => 500,
                        'returned' => 3,
                    ],
                ],
            ]);

        $items = $response->json('data.items');
        $this->assertCount(3, $items);

        // Items should be in ascending order
        $this->assertEquals(600, $items[0]['order_index']);
        $this->assertEquals(700, $items[1]['order_index']);
        $this->assertEquals(800, $items[2]['order_index']);
    }

    /** @test */
    public function it_uses_default_limit_of_5()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=1000&direction=before"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'meta' => [
                        'limit' => 5,
                    ],
                ],
            ]);

        $items = $response->json('data.items');
        $this->assertLessThanOrEqual(5, count($items));
    }

    /** @test */
    public function it_respects_custom_limit()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=1000&direction=before&limit=2"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'meta' => [
                        'limit' => 2,
                        'returned' => 2,
                    ],
                ],
            ]);

        $items = $response->json('data.items');
        $this->assertCount(2, $items);
    }

    /** @test */
    public function it_enforces_maximum_limit_of_50()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=1000&direction=before&limit=100"
        );

        $response->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertLessThanOrEqual(50, count($items));
    }

    /** @test */
    public function it_returns_has_more_true_when_more_content_exists()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=500&direction=before&limit=2"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'meta' => [
                        'has_more' => true,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_returns_has_more_false_at_beginning_of_statute()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=200&direction=before&limit=5"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'meta' => [
                        'has_more' => false,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_returns_has_more_false_at_end_of_statute()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=900&direction=after&limit=5"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'meta' => [
                        'has_more' => false,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_returns_next_from_order_for_pagination()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=500&direction=before&limit=3"
        );

        $response->assertStatus(200);

        $meta = $response->json('data.meta');
        $this->assertNotNull($meta['next_from_order']);
        $this->assertEquals(200, $meta['next_from_order']); // Minimum order_index from returned items
    }

    /** @test */
    public function it_returns_null_next_from_order_when_no_more_content()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=200&direction=before&limit=5"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'meta' => [
                        'next_from_order' => null,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_validates_required_parameters()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential"
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_order', 'direction']);
    }

    /** @test */
    public function it_validates_direction_parameter()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=500&direction=invalid"
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['direction']);
    }

    /** @test */
    public function it_validates_from_order_is_positive()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=-1&direction=before"
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_order']);
    }

    /** @test */
    public function it_validates_limit_is_positive()
    {
        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=500&direction=before&limit=0"
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }

    /** @test */
    public function it_can_exclude_children()
    {
        // Create a division with children
        $parentDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent-division',
            'division_type' => 'chapter',
            'division_number' => '11',
            'division_title' => 'Parent Division',
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
            'division_number' => '1',
            'division_title' => 'Child Division',
            'level' => 2,
            'order_index' => 560,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=600&direction=before&limit=5&include_children=false"
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
    public function it_handles_mixed_divisions_and_provisions()
    {
        // Create provisions interspersed with divisions
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
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=400&direction=before&limit=5"
        );

        $response->assertStatus(200);

        $items = $response->json('data.items');
        $types = array_column($items, 'type');

        // Should contain both divisions and provisions
        $this->assertContains('division', $types);
        $this->assertContains('provision', $types);
    }

    /** @test */
    public function it_returns_empty_array_when_no_content_exists()
    {
        // Create a new statute with no content
        $emptyStatute = Statute::create([
            'slug' => 'empty-statute',
            'title' => 'Empty Statute',
            'status' => 'published',
            'enacted_date' => now(),
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson(
            "/api/statutes/{$emptyStatute->slug}/content/sequential?from_order=500&direction=before&limit=5"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'items' => [],
                    'meta' => [
                        'returned' => 0,
                        'has_more' => false,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Create a fresh application instance without authentication
        $this->refreshApplication();

        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/sequential?from_order=500&direction=before"
        );

        $response->assertStatus(401);
    }
}
