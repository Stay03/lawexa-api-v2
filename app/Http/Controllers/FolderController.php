<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFolderRequest;
use App\Http\Requests\FolderItemRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Http\Resources\FolderCollection;
use App\Http\Resources\FolderItemCollection;
use App\Http\Resources\FolderResource;
use App\Http\Responses\ApiResponse;
use App\Models\CourtCase;
use App\Models\Folder;
use App\Models\Note;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Folder::with(['user:id,name', 'children'])
            ->withCount('bookmarks')
            ->withUserBookmark($request->user())
            ->accessibleByUser($request->user()->id);

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === '') {
                $query->rootFolders();
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        } else {
            $query->rootFolders();
        }

        if ($request->has('is_public')) {
            $isPublic = filter_var($request->is_public, FILTER_VALIDATE_BOOLEAN);
            if ($isPublic) {
                $query->public();
            } else {
                $query->private();
            }
        }

        $folders = $query->orderBySortOrder()
            ->orderByName()
            ->paginate($request->get('per_page', 15));

        $folderCollection = new FolderCollection($folders);

        return ApiResponse::success(
            $folderCollection->toArray($request),
            'Folders retrieved successfully'
        );
    }

    public function mine(Request $request): JsonResponse
    {
        $query = Folder::with(['user:id,name', 'children'])
            ->withCount('bookmarks')
            ->withUserBookmark($request->user())
            ->forUser($request->user()->id);

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === '') {
                $query->rootFolders();
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        } else {
            $query->rootFolders();
        }

        if ($request->has('is_public')) {
            $isPublic = filter_var($request->is_public, FILTER_VALIDATE_BOOLEAN);
            if ($isPublic) {
                $query->public();
            } else {
                $query->private();
            }
        }

        $folders = $query->orderBySortOrder()
            ->orderByName()
            ->paginate($request->get('per_page', 15));

        $folderCollection = new FolderCollection($folders);

        return ApiResponse::success(
            $folderCollection->toArray($request),
            'My folders retrieved successfully'
        );
    }

    public function store(CreateFolderRequest $request): JsonResponse
    {
        $folder = Folder::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'user_id' => $request->user()->id,
            'is_public' => $request->is_public ?? false,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return ApiResponse::success(
            new FolderResource($folder),
            'Folder created successfully',
            201
        );
    }

    public function show(Request $request, Folder $folder): JsonResponse
    {
        if (! $folder->isOwnedBy($request->user()) && ! $folder->isPublic()) {
            return ApiResponse::error('Folder not found', 404);
        }

        // Load basic folder information (without items for separate handling)
        $folder->load([
            'user:id,name',
            'children',
            'parent',
            'userBookmarks' => function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)->select('id', 'bookmarkable_type', 'bookmarkable_id', 'user_id');
            }
        ])->loadCount('bookmarks');

        // Handle folder items with pagination and filtering separately
        $itemsQuery = $folder->items()->with('folderable');

        // Filter by item type if specified
        if ($request->has('item_type')) {
            $itemTypes = explode(',', $request->item_type);
            $itemsQuery->whereIn('folderable_type', array_map(function ($type) {
                return match ($type) {
                    'case' => 'App\Models\CourtCase',
                    'note' => 'App\Models\Note',
                    'statute' => 'App\Models\Statute',
                    'statute_provision' => 'App\Models\StatuteProvision',
                    'statute_division' => 'App\Models\StatuteDivision',
                    default => $type
                };
            }, $itemTypes));
        }

        // Sort items if specified
        if ($request->has('sort_by')) {
            $sortBy = $request->sort_by;
            $sortOrder = $request->get('sort_order', 'asc');

            if ($sortBy === 'created_at') {
                $itemsQuery->orderBy('created_at', $sortOrder);
            } elseif ($sortBy === 'updated_at') {
                $itemsQuery->orderBy('updated_at', $sortOrder);
            } elseif ($sortBy === 'type') {
                $itemsQuery->orderBy('folderable_type', $sortOrder);
            }
        } else {
            $itemsQuery->orderBy('created_at', 'desc');
        }

        // Paginate items
        $perPage = min($request->get('per_page', 15), 50); // Max 50 items per page
        $paginatedItems = $itemsQuery->paginate($perPage);

        // Create the folder resource
        $folderResource = new FolderResource($folder);
        $folderData = $folderResource->toArray($request);

        // Add the paginated items using the new collection
        $folderItemCollection = new FolderItemCollection($paginatedItems);
        $folderData['items'] = $folderItemCollection->toArray($request);

        // Follow the consistent API pattern: wrap in resource key
        return ApiResponse::success([
            'folder' => $folderData,
        ], 'Folder retrieved successfully');
    }

    public function update(UpdateFolderRequest $request, Folder $folder): JsonResponse
    {
        if (! $folder->isOwnedBy($request->user())) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $folder->update($request->validated());

        return ApiResponse::success(
            new FolderResource($folder),
            'Folder updated successfully'
        );
    }

    public function destroy(Request $request, Folder $folder): JsonResponse
    {
        if (! $folder->isOwnedBy($request->user())) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $folder->delete();

        return ApiResponse::success(
            null,
            'Folder deleted successfully'
        );
    }

    public function addItem(FolderItemRequest $request, Folder $folder): JsonResponse
    {
        if (! $folder->isOwnedBy($request->user())) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $item = $this->findFolderableItem($request->item_type, $request->item_id);

        if (! $item) {
            return ApiResponse::error('Item not found', 404);
        }

        $success = $item->addToFolder($folder);

        if (! $success) {
            return ApiResponse::error('Item already in folder', 422);
        }

        return ApiResponse::success(
            null,
            'Item added to folder successfully'
        );
    }

    public function removeItem(FolderItemRequest $request, Folder $folder): JsonResponse
    {
        if (! $folder->isOwnedBy($request->user())) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $item = $this->findFolderableItem($request->item_type, $request->item_id);

        if (! $item) {
            return ApiResponse::error('Item not found', 404);
        }

        $success = $item->removeFromFolder($folder);

        if (! $success) {
            return ApiResponse::error('Item not in folder', 422);
        }

        return ApiResponse::success(
            null,
            'Item removed from folder successfully'
        );
    }

    public function getChildren(Request $request, Folder $folder): JsonResponse
    {
        if (! $folder->isOwnedBy($request->user()) && ! $folder->isPublic()) {
            return ApiResponse::error('Folder not found', 404);
        }

        $children = $folder->children()
            ->with(['user:id,name'])
            ->orderBySortOrder()
            ->orderByName()
            ->get();

        return ApiResponse::success(
            FolderResource::collection($children),
            'Child folders retrieved successfully'
        );
    }

    private function findFolderableItem(string $type, int $id)
    {
        return match ($type) {
            'case' => CourtCase::find($id),
            'note' => Note::find($id),
            'statute' => Statute::find($id),
            'statute_provision' => StatuteProvision::find($id),
            'statute_division' => StatuteDivision::find($id),
            default => null,
        };
    }
}
