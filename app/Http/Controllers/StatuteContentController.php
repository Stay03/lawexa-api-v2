<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Services\ContentResolverService;
use App\Services\BreadcrumbBuilderService;
use App\Services\SequentialNavigatorService;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Statute Content Controller
 *
 * Handles lazy loading endpoints for statute content.
 * Supports hash-first navigation and bidirectional sequential loading.
 */
class StatuteContentController extends Controller
{
    public function __construct(
        private ContentResolverService $contentResolver,
        private BreadcrumbBuilderService $breadcrumbBuilder,
        private SequentialNavigatorService $sequentialNavigator
    ) {}

    /**
     * Universal content lookup by slug
     *
     * GET /api/statutes/{statute}/content/{contentSlug}
     *
     * @param Request $request
     * @param Statute $statute
     * @param string $contentSlug
     * @return JsonResponse
     */
    public function lookup(Request $request, Statute $statute, string $contentSlug): JsonResponse
    {
        // Validate request parameters
        $includeChildren = $request->boolean('include_children', true);
        $includeBreadcrumb = $request->boolean('include_breadcrumb', true);
        $includeSiblings = $request->boolean('include_siblings', false);

        try {
            // Resolve content by slug
            $resolution = $this->contentResolver->resolveBySlug($statute, $contentSlug);

            $response = [
                'type' => $resolution['type'],
                'content' => $resolution['content'],
                'position' => $resolution['position']
            ];

            // Add breadcrumb if requested
            if ($includeBreadcrumb) {
                $response['breadcrumb'] = $this->breadcrumbBuilder->build(
                    $resolution['content'],
                    $statute
                );
            } else {
                $response['breadcrumb'] = null;
            }

            // Add children if requested
            if ($includeChildren) {
                $response['children'] = $this->loadChildren($resolution['content'], $resolution['type']);
            } else {
                $response['children'] = null;
            }

            // Add siblings if requested
            if ($includeSiblings) {
                $response['siblings'] = $this->loadSiblings($resolution['content'], $resolution['type']);
            } else {
                $response['siblings'] = null;
            }

            return ApiResponse::success($response, 'Content retrieved successfully');

        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return ApiResponse::error($e->getMessage(), null, 404);
        } catch (\Exception $e) {
            return ApiResponse::error('Error retrieving content: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Sequential content navigation (before/after)
     *
     * GET /api/statutes/{statute}/content/sequential
     *
     * @param Request $request
     * @param Statute $statute
     * @return JsonResponse
     */
    public function sequential(Request $request, Statute $statute): JsonResponse
    {
        // Validate required parameters
        $request->validate([
            'from_order' => 'required|integer|min:1',
            'direction' => 'required|in:before,after',
            'limit' => 'nullable|integer|min:1', // Max is enforced by the service (clamped to 50)
            'include_children' => 'nullable|in:true,false,1,0' // Accept string or boolean values
        ]);

        $fromOrder = $request->integer('from_order');
        $direction = $request->input('direction');
        $limit = $request->integer('limit', 5);
        $includeChildren = $request->boolean('include_children', true);

        try {
            // Load content based on direction
            if ($direction === 'before') {
                $result = $this->sequentialNavigator->loadBefore($statute, $fromOrder, $limit, $includeChildren);
            } else {
                $result = $this->sequentialNavigator->loadAfter($statute, $fromOrder, $limit, $includeChildren);
            }

            return ApiResponse::success($result, 'Sequential content retrieved successfully');

        } catch (\Exception $e) {
            return ApiResponse::error('Error loading sequential content: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Range-based content loading
     *
     * GET /api/statutes/{statute}/content/range
     *
     * @param Request $request
     * @param Statute $statute
     * @return JsonResponse
     */
    public function range(Request $request, Statute $statute): JsonResponse
    {
        // Validate parameters
        $request->validate([
            'start_order' => 'required|integer|min:1',
            'end_order' => 'required|integer|min:1',
            'include_children' => 'nullable|in:true,false,1,0' // Accept string or boolean values
        ]);

        $startOrder = $request->integer('start_order');
        $endOrder = $request->integer('end_order');
        $includeChildren = $request->boolean('include_children', true);

        // Validate range
        if ($endOrder < $startOrder) {
            return ApiResponse::error('end_order must be greater than or equal to start_order', null, 422);
        }

        // Note: We don't validate the numeric range here because order indices can be sparse
        // The service will validate the actual count of returned items

        try {
            $result = $this->sequentialNavigator->loadRange(
                $statute,
                $startOrder,
                $endOrder,
                $includeChildren
            );

            return ApiResponse::success($result, 'Content range retrieved successfully');

        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Error loading content range: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Load children for content
     *
     * @param mixed $content
     * @param string $type
     * @return array
     */
    private function loadChildren($content, string $type): array
    {
        if ($type === 'division') {
            // Load child divisions
            $childDivisions = $content->childDivisions()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->limit(20)
                ->get(['id', 'slug', 'division_type', 'division_number', 'division_title', 'order_index', 'level'])
                ->toArray();

            // Load provisions
            $provisions = $content->provisions()
                ->where('status', 'active')
                ->whereNull('parent_provision_id')
                ->orderBy('sort_order')
                ->limit(20)
                ->get(['id', 'slug', 'provision_type', 'provision_number', 'provision_title', 'order_index', 'level'])
                ->toArray();

            return array_merge($childDivisions, $provisions);
        } else {
            // Load child provisions
            return $content->childProvisions()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->limit(20)
                ->get(['id', 'slug', 'provision_type', 'provision_number', 'provision_title', 'order_index', 'level'])
                ->toArray();
        }
    }

    /**
     * Load siblings for content
     *
     * @param mixed $content
     * @param string $type
     * @return array
     */
    private function loadSiblings($content, string $type): array
    {
        if ($type === 'division') {
            return $content->statute
                ->divisions()
                ->where('parent_division_id', $content->parent_division_id)
                ->where('id', '!=', $content->id)
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get(['id', 'slug', 'division_type', 'division_number', 'division_title', 'order_index'])
                ->toArray();
        } else {
            return $content->statute
                ->provisions()
                ->where('division_id', $content->division_id)
                ->where('parent_provision_id', $content->parent_provision_id)
                ->where('id', '!=', $content->id)
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get(['id', 'slug', 'provision_type', 'provision_number', 'provision_title', 'order_index'])
                ->toArray();
        }
    }
}
