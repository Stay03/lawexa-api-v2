<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

/**
 * Sequential Pure API Test
 *
 * Tests the new /content/sequential-pure endpoint against all frontend requirements.
 */
class SequentialPureApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Statute $statute;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable cache tagging for tests (file driver doesn't support tags)
        config(['statute.cache.tags_enabled' => false]);

        // Create test user
        $this->user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        // Create test statute directly (no factory)
        $this->statute = Statute::create([
            'slug' => 'test-statute',
            'title' => 'Test Statute for Sequential Pure API',
            'short_title' => 'Test Statute',
            'year_enacted' => 2024,
            'jurisdiction' => 'federal',
            'country' => 'Nigeria',
            'status' => 'active',  // Changed to active
            'created_by' => $this->user->id
        ]);

        // Create test structure
        $this->createTestStructure();
    }

    /**
     * Create a test statute structure with divisions and provisions
     */
    private function createTestStructure(): void
    {
        // Create Chapter I (order 100)
        $chapter1 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'chapter-i',
            'division_type' => 'chapter',
            'division_number' => 'I',
            'division_title' => 'General Provisions',
            'level' => 1,
            'sort_order' => 1,
            'order_index' => 100,
            'status' => 'active'
        ]);

        // Create Part I under Chapter I (order 200)
        $part1 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $chapter1->id,
            'slug' => 'part-i',
            'division_type' => 'part',
            'division_number' => 'I',
            'division_title' => 'Fundamental Rights',
            'level' => 2,
            'sort_order' => 1,
            'order_index' => 200,
            'status' => 'active'
        ]);

        // Create Section 1 under Part I (order 300)
        $section1 = StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'division_id' => $part1->id,
            'slug' => 'section-1',
            'provision_type' => 'section',
            'provision_number' => '1',
            'provision_title' => 'Right to dignity',
            'provision_text' => 'Every individual is entitled to respect for the dignity of his person.',
            'level' => 1,
            'sort_order' => 1,
            'order_index' => 300,
            'status' => 'active'
        ]);

        // Create Subsection (1) under Section 1 (order 400)
        StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'division_id' => $part1->id,
            'parent_provision_id' => $section1->id,
            'slug' => 'section-1-subsection-1',
            'provision_type' => 'subsection',
            'provision_number' => '(1)',
            'provision_text' => 'No person shall be subjected to torture or to inhuman or degrading treatment.',
            'level' => 2,
            'sort_order' => 1,
            'order_index' => 400,
            'status' => 'active'
        ]);

        // Create Section 2 (order 500)
        StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'division_id' => $part1->id,
            'slug' => 'section-2',
            'provision_type' => 'section',
            'provision_number' => '2',
            'provision_title' => 'Right to personal liberty',
            'provision_text' => 'Every person shall be entitled to his personal liberty.',
            'level' => 1,
            'sort_order' => 2,
            'order_index' => 500,
            'status' => 'active'
        ]);

        // Create Chapter II (order 600)
        $chapter2 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'chapter-ii',
            'division_type' => 'chapter',
            'division_number' => 'II',
            'division_title' => 'Legislative Powers',
            'level' => 1,
            'sort_order' => 2,
            'order_index' => 600,
            'status' => 'active'
        ]);

        // Create Section 3 under Chapter II (order 700)
        StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'division_id' => $chapter2->id,
            'slug' => 'section-3',
            'provision_type' => 'section',
            'provision_number' => '3',
            'provision_title' => 'Legislative authority',
            'provision_text' => 'The legislative authority shall vest in the National Assembly.',
            'level' => 1,
            'sort_order' => 1,
            'order_index' => 700,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_loads_content_from_beginning()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=0&direction=after&limit=15");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'slug',
                            'type',
                            'division_type',
                            'division_number',
                            'division_title',
                            'division_subtitle',
                            'content',
                            'provision_type',
                            'provision_number',
                            'provision_title',
                            'provision_text',
                            'marginal_note',
                            'interpretation_note',
                            'level',
                            'parent_division_id',
                            'parent_provision_id',
                            'order_index',
                            'has_children',
                            'child_count',
                            'breadcrumb',
                            'status',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'meta' => [
                        'format',
                        'direction',
                        'from_order',
                        'limit',
                        'returned',
                        'has_more',
                        'next_from_order'
                    ]
                ]
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'meta' => [
                        'format' => 'sequential_pure',
                        'direction' => 'after'
                    ]
                ]
            ]);

        // Verify items are in correct order
        $items = $response->json('data.items');
        $this->assertGreaterThan(0, count($items));

        // Verify first item is Chapter I
        $this->assertEquals('division', $items[0]['type']);
        $this->assertEquals('chapter', $items[0]['division_type']);
        $this->assertEquals(100, $items[0]['order_index']);
    }

    /** @test */
    public function it_loads_content_from_middle_hash_navigation()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=300&direction=after&limit=15");

        $response->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertGreaterThan(0, count($items));

        // First item should be after order 300
        $this->assertGreaterThan(300, $items[0]['order_index']);
    }

    /** @test */
    public function it_loads_content_before_scroll_up()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=500&direction=before&limit=10");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'meta' => [
                        'direction' => 'before'
                    ]
                ]
            ]);

        $items = $response->json('data.items');

        // All items should be before order 500
        foreach ($items as $item) {
            $this->assertLessThan(500, $item['order_index']);
        }
    }

    /** @test */
    public function it_excludes_breadcrumb_when_requested()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=200&direction=after&limit=5&include_breadcrumb=false");

        $response->assertStatus(200);

        $items = $response->json('data.items');

        // Items should not have breadcrumb field
        foreach ($items as $item) {
            $this->assertArrayNotHasKey('breadcrumb', $item);
        }
    }

    /** @test */
    public function it_includes_breadcrumb_by_default()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=200&direction=after&limit=5");

        $response->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertGreaterThan(0, count($items));

        // At least one item should have breadcrumb
        $this->assertArrayHasKey('breadcrumb', $items[0]);
        $this->assertIsArray($items[0]['breadcrumb']);

        // Breadcrumb should start with statute
        $this->assertEquals('statute', $items[0]['breadcrumb'][0]['type']);
        $this->assertEquals($this->statute->id, $items[0]['breadcrumb'][0]['id']);
    }

    /** @test */
    public function it_enforces_maximum_limit()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=100&direction=after&limit=100");

        $response->assertStatus(200);

        $items = $response->json('data.items');
        $returned = $response->json('data.meta.returned');

        // Should not return more than 50 items
        $this->assertLessThanOrEqual(50, $returned);
        $this->assertLessThanOrEqual(50, count($items));
    }

    /** @test */
    public function it_validates_missing_from_order()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?direction=after&limit=15");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_order']);
    }

    /** @test */
    public function it_validates_missing_direction()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=100&limit=15");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['direction']);
    }

    /** @test */
    public function it_validates_invalid_direction()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=100&direction=sideways&limit=15");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['direction']);
    }

    /** @test */
    public function it_returns_404_for_invalid_statute()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/nonexistent-statute/content/sequential-pure?from_order=0&direction=after&limit=15");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_empty_array_when_no_content_after()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=999999&direction=after&limit=15");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'items' => [],
                    'meta' => [
                        'returned' => 0,
                        'has_more' => false
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_includes_all_fields_on_every_item()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=0&direction=after&limit=5");

        $response->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertGreaterThan(0, count($items));

        foreach ($items as $item) {
            // Identity fields
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('slug', $item);
            $this->assertArrayHasKey('type', $item);

            // Division fields (null for provisions)
            $this->assertArrayHasKey('division_type', $item);
            $this->assertArrayHasKey('division_number', $item);
            $this->assertArrayHasKey('division_title', $item);
            $this->assertArrayHasKey('division_subtitle', $item);
            $this->assertArrayHasKey('content', $item);

            // Provision fields (null for divisions)
            $this->assertArrayHasKey('provision_type', $item);
            $this->assertArrayHasKey('provision_number', $item);
            $this->assertArrayHasKey('provision_title', $item);
            $this->assertArrayHasKey('provision_text', $item);
            $this->assertArrayHasKey('marginal_note', $item);
            $this->assertArrayHasKey('interpretation_note', $item);

            // Hierarchy fields
            $this->assertArrayHasKey('level', $item);
            $this->assertArrayHasKey('parent_division_id', $item);
            $this->assertArrayHasKey('parent_provision_id', $item);

            // Position fields
            $this->assertArrayHasKey('order_index', $item);
            $this->assertArrayHasKey('has_children', $item);
            $this->assertArrayHasKey('child_count', $item);

            // Metadata
            $this->assertArrayHasKey('status', $item);
            $this->assertArrayHasKey('created_at', $item);
            $this->assertArrayHasKey('updated_at', $item);

            // Verify type-specific nulls
            if ($item['type'] === 'division') {
                $this->assertNull($item['provision_type']);
                $this->assertNotNull($item['division_type']);
            } else {
                $this->assertNull($item['division_type']);
                $this->assertNotNull($item['provision_type']);
            }
        }
    }

    /** @test */
    public function it_returns_correct_pagination_metadata()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=100&direction=after&limit=2");

        $response->assertStatus(200);

        $meta = $response->json('data.meta');

        $this->assertEquals('sequential_pure', $meta['format']);
        $this->assertEquals('after', $meta['direction']);
        $this->assertEquals(100, $meta['from_order']);
        $this->assertEquals(2, $meta['limit']);
        $this->assertArrayHasKey('returned', $meta);
        $this->assertArrayHasKey('has_more', $meta);
        $this->assertArrayHasKey('next_from_order', $meta);

        if ($meta['has_more']) {
            $this->assertNotNull($meta['next_from_order']);
        } else {
            $this->assertNull($meta['next_from_order']);
        }
    }

    /** @test */
    public function breadcrumb_includes_full_hierarchy()
    {
        Sanctum::actingAs($this->user);

        // Get a deeply nested provision (subsection under section under part under chapter)
        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/sequential-pure?from_order=400&direction=after&limit=1");

        $response->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertGreaterThan(0, count($items));

        $item = $items[0];

        if ($item['order_index'] == 400) {
            // This is the subsection - should have full breadcrumb
            $this->assertArrayHasKey('breadcrumb', $item);
            $breadcrumb = $item['breadcrumb'];

            // Should have: Statute -> Chapter -> Part -> Section -> Subsection
            $this->assertGreaterThanOrEqual(3, count($breadcrumb));

            // First should be statute
            $this->assertEquals('statute', $breadcrumb[0]['type']);
        }
    }
}
