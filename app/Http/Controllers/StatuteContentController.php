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

            // Add children if requested - use type-specific keys
            if ($includeChildren) {
                $children = $this->loadChildren($resolution['content'], $resolution['type']);

                // For divisions: add childDivisions and provisions
                if ($resolution['type'] === 'division') {
                    $response['childDivisions'] = $children['childDivisions'];
                    $response['provisions'] = $children['provisions'];
                } else {
                    // For provisions: add childProvisions
                    $response['childProvisions'] = $children['childProvisions'];
                }
            } else {
                // Set empty arrays when children not included
                if ($resolution['type'] === 'division') {
                    $response['childDivisions'] = [];
                    $response['provisions'] = [];
                } else {
                    $response['childProvisions'] = [];
                }
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
            'include_children' => 'nullable|in:true,false,1,0', // Accept string or boolean values
            'format' => 'nullable|in:nested,flat' // Format parameter: nested (default) or flat
        ]);

        $fromOrder = $request->integer('from_order');
        $direction = $request->input('direction');
        $limit = $request->integer('limit', 5);
        $includeChildren = $request->boolean('include_children', true);
        $format = $request->input('format', 'nested'); // Default to nested for backward compatibility

        try {
            // Load content based on direction
            if ($direction === 'before') {
                $result = $this->sequentialNavigator->loadBefore($statute, $fromOrder, $limit, $includeChildren, $format);
            } else {
                $result = $this->sequentialNavigator->loadAfter($statute, $fromOrder, $limit, $includeChildren, $format);
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
            'include_children' => 'nullable|in:true,false,1,0', // Accept string or boolean values
            'format' => 'nullable|in:nested,flat' // Format parameter: nested (default) or flat
        ]);

        $startOrder = $request->integer('start_order');
        $endOrder = $request->integer('end_order');
        $includeChildren = $request->boolean('include_children', true);
        $format = $request->input('format', 'nested'); // Default to nested for backward compatibility

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
                $includeChildren,
                $format
            );

            return ApiResponse::success($result, 'Content range retrieved successfully');

        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Error loading content range: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Sequential content navigation in pure format (flat with all fields at root level)
     *
     * GET /api/statutes/{statute}/content/sequential-pure
     *
     * This endpoint is optimized for frontend lazy loading with:
     * - All fields at root level (no content wrapper)
     * - Both division and provision fields on every item (type-specific ones are null)
     * - Optional breadcrumb on each item
     * - Pure flat list structure
     * - Support for slug-based navigation (from_slug) as alternative to from_order
     *
     * @param Request $request
     * @param Statute $statute
     * @return JsonResponse
     */
    public function sequentialPure(Request $request, Statute $statute): JsonResponse
    {
        // Validate required parameters
        $request->validate([
            'from_order' => 'required_without:from_slug|integer|min:0',
            'from_slug' => 'required_without:from_order|string',
            'direction' => 'required|in:before,after,at',
            'limit' => 'nullable|integer|min:1',  // Max is enforced by service (clamped to 50)
            'include_breadcrumb' => 'nullable|in:true,false,1,0'
        ]);

        // Ensure only one of from_order or from_slug is provided
        if ($request->filled('from_order') && $request->filled('from_slug')) {
            return ApiResponse::error(
                'Validation failed',
                ['from_slug' => ['Cannot use both from_order and from_slug. Please provide only one.']],
                422
            );
        }

        // Resolve from_slug to from_order if provided
        $fromOrder = null;
        $fromSlug = null;
        $resolvedOrderIndex = null;

        if ($request->filled('from_slug')) {
            $fromSlug = $request->input('from_slug');

            try {
                // Resolve slug to order_index using existing ContentResolverService
                $resolution = $this->contentResolver->resolveBySlug($statute, $fromSlug);
                $fromOrder = $resolution['order_index'];
                $resolvedOrderIndex = $fromOrder;

            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                return ApiResponse::error(
                    "Content with slug '{$fromSlug}' not found in this statute",
                    ['from_slug' => ['The specified slug does not exist in this statute.']],
                    404
                );
            }
        } else {
            $fromOrder = $request->integer('from_order');
        }

        $direction = $request->input('direction');
        $limit = $request->integer('limit', 15);
        $includeBreadcrumb = $request->boolean('include_breadcrumb', true);

        try {
            // Load sequential content in pure format
            $result = $this->sequentialNavigator->loadSequentialPure(
                $statute,
                $fromOrder,
                $direction,
                $limit,
                $includeBreadcrumb
            );

            // Add from_slug and resolved_order_index to meta if slug was used
            if ($fromSlug !== null) {
                $result['meta']['from_slug'] = $fromSlug;
                $result['meta']['resolved_order_index'] = $resolvedOrderIndex;
            }

            return ApiResponse::success($result, 'Sequential content retrieved successfully');

        } catch (\Exception $e) {
            return ApiResponse::error('Error loading sequential content: ' . $e->getMessage(), null, 500);
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
            // Load child divisions with parent reference and has_children flag
            $childDivisions = $content->childDivisions()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->limit(20)
                ->get([
                    'id', 'slug', 'division_type', 'division_number', 'division_title',
                    'division_subtitle', 'content', 'parent_division_id', 'level',
                    'order_index', 'status'
                ])
                ->map(function ($div) {
                    $divArray = $div->toArray();
                    // Check if division has children (child divisions or provisions)
                    $divArray['has_children'] = $div->childDivisions()->where('status', 'active')->exists()
                        || $div->provisions()->where('status', 'active')->exists();
                    return $divArray;
                })
                ->toArray();

            // Load provisions at this division level with parent references
            $provisions = $content->provisions()
                ->where('status', 'active')
                ->whereNull('parent_provision_id')
                ->orderBy('sort_order')
                ->limit(20)
                ->get([
                    'id', 'slug', 'provision_type', 'provision_number', 'provision_title',
                    'provision_text', 'marginal_note', 'interpretation_note',
                    'division_id', 'parent_provision_id', 'level', 'order_index', 'status'
                ])
                ->map(function ($prov) {
                    $provArray = $prov->toArray();
                    // Check if provision has child provisions
                    $provArray['has_children'] = $prov->childProvisions()->where('status', 'active')->exists();
                    // Recursively load child provisions if has children
                    $provArray['childProvisions'] = [];
                    return $provArray;
                })
                ->toArray();

            // Return separate arrays for child divisions and provisions
            return [
                'childDivisions' => $childDivisions,
                'provisions' => $provisions
            ];
        } else {
            // Load child provisions with parent reference and has_children flag
            $childProvisions = $content->childProvisions()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->limit(20)
                ->get([
                    'id', 'slug', 'provision_type', 'provision_number', 'provision_title',
                    'provision_text', 'marginal_note', 'interpretation_note',
                    'division_id', 'parent_provision_id', 'level', 'order_index', 'status'
                ])
                ->map(function ($prov) {
                    $provArray = $prov->toArray();
                    // Check if provision has child provisions
                    $provArray['has_children'] = $prov->childProvisions()->where('status', 'active')->exists();
                    return $provArray;
                })
                ->toArray();

            // Return child provisions array
            return [
                'childProvisions' => $childProvisions
            ];
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
