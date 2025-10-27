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

class StatuteContentLookupTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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
    }

    /** @test */
    public function it_can_lookup_division_by_slug()
    {
        // Create a division with order_index
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_number' => 'I',
            'division_title' => 'Test Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/{$division->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'type',
                    'content' => [
                        'id',
                        'slug',
                        'division_type',
                        'division_title',
                        'order_index',
                    ],
                    'breadcrumb',
                    'children',
                    'position' => [
                        'order_index',
                        'total_items',
                        'has_content_before',
                        'has_content_after',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'type' => 'division',
                    'content' => [
                        'id' => $division->id,
                        'slug' => $division->slug,
                    ],
                    'position' => [
                        'order_index' => 100,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_lookup_provision_by_slug()
    {
        // Create a provision with order_index
        $provision = StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-provision',
            'provision_type' => 'section',
            'provision_number' => '1',
            'provision_title' => 'Test Provision',
            'provision_text' => 'This is a test provision.',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/{$provision->slug}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'type' => 'provision',
                    'content' => [
                        'id' => $provision->id,
                        'slug' => $provision->slug,
                        'provision_text' => 'This is a test provision.',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_includes_breadcrumb_by_default()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_number' => '1',
            'division_title' => 'Test Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/{$division->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'breadcrumb' => [
                        '*' => ['id', 'slug', 'title', 'type'],
                    ],
                ],
            ]);

        // Check that breadcrumb includes statute and division
        $breadcrumb = $response->json('data.breadcrumb');
        $this->assertGreaterThanOrEqual(2, count($breadcrumb));
        $this->assertEquals('statute', $breadcrumb[0]['type']);
    }

    /** @test */
    public function it_can_exclude_breadcrumb()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_number' => '1',
            'division_title' => 'Test Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/{$division->slug}?include_breadcrumb=false"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'breadcrumb' => null,
                ],
            ]);
    }

    /** @test */
    public function it_includes_children_by_default()
    {
        $parentDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent-division',
            'division_type' => 'chapter',
            'division_number' => '1',
            'division_title' => 'Parent Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
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
            'order_index' => 200,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/{$parentDivision->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'children' => [
                        '*' => ['id', 'slug'],
                    ],
                ],
            ]);

        $children = $response->json('data.children');
        $this->assertGreaterThan(0, count($children));
        $this->assertEquals($childDivision->id, $children[0]['id']);
    }

    /** @test */
    public function it_can_exclude_children()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_number' => '1',
            'division_title' => 'Test Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/{$division->slug}?include_children=false"
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'children' => null,
                ],
            ]);
    }

    /** @test */
    public function it_can_include_siblings()
    {
        $division1 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
            'division_number' => '1',
            'division_title' => 'Division 1',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $division2 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-2',
            'division_type' => 'chapter',
            'division_number' => '2',
            'division_title' => 'Division 2',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $response = $this->getJson(
            "/api/statutes/{$this->statute->slug}/content/{$division1->slug}?include_siblings=true"
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'siblings' => [
                        '*' => ['id', 'slug'],
                    ],
                ],
            ]);

        $siblings = $response->json('data.siblings');
        $this->assertGreaterThan(0, count($siblings));
        $this->assertEquals($division2->id, $siblings[0]['id']);
    }

    /** @test */
    public function it_returns_correct_position_metadata()
    {
        // Create multiple divisions with order indices
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
            'division_number' => '1',
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
            'division_number' => '2',
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
            'division_number' => '3',
            'division_title' => 'Division 3',
            'level' => 1,
            'order_index' => 300,
            'sort_order' => 3,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/{$targetDivision->slug}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'position' => [
                        'order_index' => 200,
                        'total_items' => 3,
                        'has_content_before' => true,
                        'has_content_after' => true,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_content()
    {
        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/non-existent-slug");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_statute()
    {
        $response = $this->getJson("/api/statutes/non-existent-statute/content/some-slug");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_number' => '1',
            'division_title' => 'Test Division',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // Create a fresh application instance without authentication
        $this->refreshApplication();

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/{$division->slug}");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_content_without_order_index()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_number' => '1',
            'division_title' => 'Test Division',
            'level' => 1,
            'order_index' => null, // No order index
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/{$division->slug}");

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    /** @test */
    public function it_returns_correct_has_content_before_flag_for_first_item()
    {
        $firstDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'first-division',
            'division_type' => 'chapter',
            'division_number' => '1',
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
            'division_number' => '2',
            'division_title' => 'Second Division',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/{$firstDivision->slug}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'position' => [
                        'has_content_before' => false,
                        'has_content_after' => true,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_returns_correct_has_content_after_flag_for_last_item()
    {
        StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'first-division',
            'division_type' => 'chapter',
            'division_number' => '1',
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
            'division_number' => '2',
            'division_title' => 'Last Division',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/statutes/{$this->statute->slug}/content/{$lastDivision->slug}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'position' => [
                        'has_content_before' => true,
                        'has_content_after' => false,
                    ],
                ],
            ]);
    }
}
