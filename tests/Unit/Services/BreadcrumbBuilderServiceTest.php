<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use App\Services\BreadcrumbBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class BreadcrumbBuilderServiceTest extends TestCase
{
    use RefreshDatabase;

    private BreadcrumbBuilderService $service;
    private Statute $statute;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BreadcrumbBuilderService();

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
    public function it_can_build_breadcrumb_for_top_level_division()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_number' => 'I',
            'division_title' => 'Test Chapter',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $breadcrumb = $this->service->build($division, $this->statute);

        $this->assertCount(2, $breadcrumb); // Statute + Division
        $this->assertEquals('statute', $breadcrumb[0]['type']);
        $this->assertEquals($this->statute->id, $breadcrumb[0]['id']);
        $this->assertEquals('chapter', $breadcrumb[1]['type']);
        $this->assertEquals($division->id, $breadcrumb[1]['id']);
    }

    /** @test */
    public function it_can_build_breadcrumb_for_nested_divisions()
    {
        $parentDivision = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent-division',
            'division_type' => 'chapter',
            'division_number' => 'I',
            'division_title' => 'Parent Chapter',
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
            'division_title' => 'Child Section',
            'level' => 2,
            'order_index' => 200,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $breadcrumb = $this->service->build($childDivision, $this->statute);

        $this->assertCount(3, $breadcrumb); // Statute + Parent + Child
        $this->assertEquals('statute', $breadcrumb[0]['type']);
        $this->assertEquals('chapter', $breadcrumb[1]['type']);
        $this->assertEquals($parentDivision->id, $breadcrumb[1]['id']);
        $this->assertEquals('section', $breadcrumb[2]['type']);
        $this->assertEquals($childDivision->id, $breadcrumb[2]['id']);
    }

    /** @test */
    public function it_can_build_breadcrumb_for_deeply_nested_divisions()
    {
        $level1 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'level-1',
            'division_type' => 'chapter',
            'division_title' => 'Level 1',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $level2 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $level1->id,
            'slug' => 'level-2',
            'division_type' => 'part',
            'division_title' => 'Level 2',
            'level' => 2,
            'order_index' => 200,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $level3 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $level2->id,
            'slug' => 'level-3',
            'division_type' => 'section',
            'division_title' => 'Level 3',
            'level' => 3,
            'order_index' => 300,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $breadcrumb = $this->service->build($level3, $this->statute);

        $this->assertCount(4, $breadcrumb); // Statute + Level1 + Level2 + Level3
        $this->assertEquals($this->statute->id, $breadcrumb[0]['id']);
        $this->assertEquals($level1->id, $breadcrumb[1]['id']);
        $this->assertEquals($level2->id, $breadcrumb[2]['id']);
        $this->assertEquals($level3->id, $breadcrumb[3]['id']);
    }

    /** @test */
    public function it_can_build_breadcrumb_for_provision()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_title' => 'Test Chapter',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $provision = StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'division_id' => $division->id,
            'slug' => 'test-provision',
            'provision_type' => 'subsection',
            'provision_number' => '1',
            'provision_text' => 'Test provision',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $breadcrumb = $this->service->build($provision, $this->statute);

        $this->assertCount(3, $breadcrumb); // Statute + Division + Provision
        $this->assertEquals('statute', $breadcrumb[0]['type']);
        $this->assertEquals('chapter', $breadcrumb[1]['type']);
        $this->assertEquals('subsection', $breadcrumb[2]['type']);
        $this->assertEquals($provision->id, $breadcrumb[2]['id']);
    }

    /** @test */
    public function it_can_build_breadcrumb_for_nested_provisions()
    {
        $parentProvision = StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent-provision',
            'provision_type' => 'section',
            'provision_number' => '1',
            'provision_text' => 'Parent provision',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $childProvision = StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'parent_provision_id' => $parentProvision->id,
            'slug' => 'child-provision',
            'provision_type' => 'subsection',
            'provision_number' => '1a',
            'provision_text' => 'Child provision',
            'level' => 2,
            'order_index' => 200,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $breadcrumb = $this->service->build($childProvision, $this->statute);

        $this->assertCount(3, $breadcrumb); // Statute + Parent + Child
        $this->assertEquals('statute', $breadcrumb[0]['type']);
        $this->assertEquals('section', $breadcrumb[1]['type']);
        $this->assertEquals($parentProvision->id, $breadcrumb[1]['id']);
        $this->assertEquals('subsection', $breadcrumb[2]['type']);
        $this->assertEquals($childProvision->id, $breadcrumb[2]['id']);
    }

    /** @test */
    public function it_caches_breadcrumb()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_title' => 'Test Chapter',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // First call - should cache
        $breadcrumb1 = $this->service->build($division, $this->statute);

        // Verify cache exists
        $cacheKey = "breadcrumb:{$this->statute->id}:division:{$division->id}";
        $cached = Cache::tags(["statute:{$this->statute->id}"])->get($cacheKey);

        $this->assertNotNull($cached);
        $this->assertEquals($breadcrumb1, $cached);
    }

    /** @test */
    public function it_can_invalidate_breadcrumb_cache()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_title' => 'Test Chapter',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // Build and cache
        $this->service->build($division, $this->statute);

        // Invalidate
        $this->service->invalidate($division, $this->statute);

        // Verify cache is cleared
        $cacheKey = "breadcrumb:{$this->statute->id}:division:{$division->id}";
        $cached = Cache::tags(["statute:{$this->statute->id}"])->get($cacheKey);

        $this->assertNull($cached);
    }

    /** @test */
    public function it_can_invalidate_entire_statute_cache()
    {
        $division1 = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'division-1',
            'division_type' => 'chapter',
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
            'division_title' => 'Division 2',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        // Build and cache both
        $this->service->build($division1, $this->statute);
        $this->service->build($division2, $this->statute);

        // Invalidate entire statute
        $this->service->invalidateStatute($this->statute);

        // Verify both caches are cleared
        $cacheKey1 = "breadcrumb:{$this->statute->id}:division:{$division1->id}";
        $cacheKey2 = "breadcrumb:{$this->statute->id}:division:{$division2->id}";

        $this->assertNull(Cache::tags(["statute:{$this->statute->id}"])->get($cacheKey1));
        $this->assertNull(Cache::tags(["statute:{$this->statute->id}"])->get($cacheKey2));
    }

    /** @test */
    public function it_includes_order_index_in_breadcrumb()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_title' => 'Test Chapter',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $breadcrumb = $this->service->build($division, $this->statute);

        // Statute should have null order_index
        $this->assertNull($breadcrumb[0]['order_index']);

        // Division should have order_index
        $this->assertEquals(100, $breadcrumb[1]['order_index']);
    }

    /** @test */
    public function it_can_build_statute_only_breadcrumb()
    {
        $breadcrumb = $this->service->buildStatuteBreadcrumb($this->statute);

        $this->assertCount(1, $breadcrumb);
        $this->assertEquals('statute', $breadcrumb[0]['type']);
        $this->assertEquals($this->statute->id, $breadcrumb[0]['id']);
        $this->assertEquals($this->statute->title, $breadcrumb[0]['title']);
        $this->assertNull($breadcrumb[0]['order_index']);
    }

    /** @test */
    public function it_includes_all_required_fields_in_breadcrumb()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_number' => 'I',
            'division_title' => 'Test Chapter',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $breadcrumb = $this->service->build($division, $this->statute);

        $divisionCrumb = $breadcrumb[1];

        $this->assertArrayHasKey('id', $divisionCrumb);
        $this->assertArrayHasKey('slug', $divisionCrumb);
        $this->assertArrayHasKey('title', $divisionCrumb);
        $this->assertArrayHasKey('number', $divisionCrumb);
        $this->assertArrayHasKey('type', $divisionCrumb);
        $this->assertArrayHasKey('order_index', $divisionCrumb);
    }

    /** @test */
    public function it_handles_provision_with_division()
    {
        $division = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'test-division',
            'division_type' => 'chapter',
            'division_title' => 'Test Chapter',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $provision = StatuteProvision::create([
            'statute_id' => $this->statute->id,
            'division_id' => $division->id,
            'slug' => 'test-provision',
            'provision_type' => 'subsection',
            'provision_number' => '1',
            'provision_text' => 'Test provision',
            'level' => 1,
            'order_index' => 200,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $breadcrumb = $this->service->build($provision, $this->statute);

        // Should include statute, division, and provision
        $this->assertCount(3, $breadcrumb);
        $this->assertEquals($this->statute->id, $breadcrumb[0]['id']);
        $this->assertEquals($division->id, $breadcrumb[1]['id']);
        $this->assertEquals($provision->id, $breadcrumb[2]['id']);
    }

    /** @test */
    public function it_maintains_correct_order_in_breadcrumb()
    {
        $parent = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'slug' => 'parent',
            'division_type' => 'chapter',
            'division_title' => 'Parent',
            'level' => 1,
            'order_index' => 100,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $child = StatuteDivision::create([
            'statute_id' => $this->statute->id,
            'parent_division_id' => $parent->id,
            'slug' => 'child',
            'division_type' => 'section',
            'division_title' => 'Child',
            'level' => 2,
            'order_index' => 200,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $breadcrumb = $this->service->build($child, $this->statute);

        // Order should be: Statute -> Parent -> Child
        $this->assertEquals($this->statute->id, $breadcrumb[0]['id']);
        $this->assertEquals($parent->id, $breadcrumb[1]['id']);
        $this->assertEquals($child->id, $breadcrumb[2]['id']);
    }
}
