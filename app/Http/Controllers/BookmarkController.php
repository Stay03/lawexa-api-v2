<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookmarkCheckRequest;
use App\Http\Requests\CreateBookmarkRequest;
use App\Http\Resources\BookmarkCollection;
use App\Http\Resources\BookmarkResource;
use App\Http\Responses\ApiResponse;
use App\Models\Bookmark;
use App\Models\CourtCase;
use App\Models\Folder;
use App\Models\Note;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    private const SUPPORTED_MODELS = [
        'App\\Models\\CourtCase' => CourtCase::class,
        'App\\Models\\Note' => Note::class,
        'App\\Models\\Statute' => Statute::class,
        'App\\Models\\StatuteDivision' => StatuteDivision::class,
        'App\\Models\\StatuteProvision' => StatuteProvision::class,
        'App\\Models\\Folder' => Folder::class,
    ];

    public function index(Request $request): JsonResponse
    {
        $query = Bookmark::with(['bookmarkable'])
            ->forUser($request->user()->id);

        if ($request->has('bookmarkable_type')) {
            $query->forType($request->bookmarkable_type);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('bookmarkable', function ($q) use ($search) {
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('content', 'like', "%{$search}%")
                            ->orWhere('body', 'like', "%{$search}%");
                });
            });
        }

        $perPage = min($request->get('per_page', 15), 50);
        $bookmarks = $query->latest()->paginate($perPage);

        return ApiResponse::success(
            new BookmarkCollection($bookmarks),
            'Bookmarks retrieved successfully'
        );
    }

    public function store(CreateBookmarkRequest $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->bookmarkable_type;
        $id = $request->bookmarkable_id;

        if (!isset(self::SUPPORTED_MODELS[$type])) {
            return ApiResponse::error('Unsupported model type', [], 422);
        }

        $modelClass = self::SUPPORTED_MODELS[$type];
        
        try {
            $item = $modelClass::findOrFail($id);
        } catch (ModelNotFoundException) {
            return ApiResponse::error('Item not found', [], 404);
        }

        $existingBookmark = Bookmark::forUser($user->id)
            ->forItem($type, $id)
            ->first();

        if ($existingBookmark) {
            return ApiResponse::error('Item is already bookmarked', [], 409);
        }

        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'bookmarkable_type' => $type,
            'bookmarkable_id' => $id,
        ]);

        $bookmark->load('bookmarkable');

        return ApiResponse::success(
            new BookmarkResource($bookmark),
            'Item bookmarked successfully',
            201
        );
    }

    public function destroy(Bookmark $bookmark): JsonResponse
    {
        $user = request()->user();

        if ($bookmark->user_id !== $user->id) {
            return ApiResponse::error('Unauthorized to remove this bookmark', [], 403);
        }

        $bookmark->delete();

        return ApiResponse::success(
            null,
            'Bookmark removed successfully'
        );
    }

    public function check(BookmarkCheckRequest $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->bookmarkable_type;
        $id = $request->bookmarkable_id;

        $isBookmarked = Bookmark::forUser($user->id)
            ->forItem($type, $id)
            ->exists();

        return ApiResponse::success([
            'is_bookmarked' => $isBookmarked
        ], 'Bookmark status checked successfully');
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $totalBookmarks = Bookmark::forUser($user->id)->count();
        
        $bookmarksByType = Bookmark::forUser($user->id)
            ->selectRaw('bookmarkable_type, COUNT(*) as count')
            ->groupBy('bookmarkable_type')
            ->get()
            ->mapWithKeys(function ($item) {
                $className = class_basename($item->bookmarkable_type);
                return [$className => $item->count];
            });

        return ApiResponse::success([
            'total_bookmarks' => $totalBookmarks,
            'bookmarks_by_type' => $bookmarksByType,
        ], 'Bookmark statistics retrieved successfully');
    }
}
