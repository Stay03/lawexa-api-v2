<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Http\Resources\NoteCollection;
use App\Http\Resources\NoteListResource;
use App\Http\Resources\NoteResource;
use App\Http\Responses\ApiResponse;
use App\Models\Note;
use App\Models\NoteVideo;
use App\Services\ViewTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(
        private ViewTrackingService $viewTrackingService
    ) {}
    public function index(Request $request): JsonResponse
    {
        $query = Note::with(['user:id,name,email,avatar', 'comments'])
                    ->withViewsCount()
                    ->withCount('bookmarks')
                    ->withUserBookmark($request->user())
                    ->accessibleByUser($request->user()->id)
                    ->where('status', 'published') // Exclude draft notes from listing
                    ->where('is_private', false); // Exclude private notes from listing

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('tag')) {
            $query->withTag($request->tag);
        }

        if ($request->has('is_private')) {
            $isPrivate = filter_var($request->is_private, FILTER_VALIDATE_BOOLEAN);
            if ($isPrivate) {
                $query->private();
            } else {
                $query->public();
            }
        }

        $notes = $query->orderByLatest()
                      ->paginate($request->get('per_page', 15));

        return ApiResponse::success([
            'notes' => NoteListResource::collection($notes),
            'meta' => [
                'current_page' => $notes->currentPage(),
                'from' => $notes->firstItem(),
                'last_page' => $notes->lastPage(),
                'per_page' => $notes->perPage(),
                'to' => $notes->lastItem(),
                'total' => $notes->total(),
            ],
            'links' => [
                'first' => $notes->url(1),
                'last' => $notes->url($notes->lastPage()),
                'prev' => $notes->previousPageUrl(),
                'next' => $notes->nextPageUrl(),
            ],
        ], 'Notes retrieved successfully');
    }

    public function myNotes(Request $request): JsonResponse
    {
        $query = Note::with(['user:id,name,email,avatar', 'comments'])
                    ->withViewsCount()
                    ->withCount('bookmarks')
                    ->withUserBookmark($request->user())
                    ->forUser($request->user()->id);

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('tag')) {
            $query->withTag($request->tag);
        }

        if ($request->has('is_private')) {
            $isPrivate = filter_var($request->is_private, FILTER_VALIDATE_BOOLEAN);
            if ($isPrivate) {
                $query->private();
            } else {
                $query->public();
            }
        }

        if ($request->has('status')) {
            if ($request->status === 'draft') {
                $query->draft();
            } elseif ($request->status === 'published') {
                $query->published();
            }
        }

        $notes = $query->orderByLatest()
                      ->paginate($request->get('per_page', 15));

        return ApiResponse::success([
            'notes' => NoteListResource::collection($notes),
            'meta' => [
                'current_page' => $notes->currentPage(),
                'from' => $notes->firstItem(),
                'last_page' => $notes->lastPage(),
                'per_page' => $notes->perPage(),
                'to' => $notes->lastItem(),
                'total' => $notes->total(),
            ],
            'links' => [
                'first' => $notes->url(1),
                'last' => $notes->url($notes->lastPage()),
                'prev' => $notes->previousPageUrl(),
                'next' => $notes->nextPageUrl(),
            ],
        ], 'My notes retrieved successfully');
    }

    public function store(CreateNoteRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        // Extract videos from validated data
        $videos = $validated['videos'] ?? null;
        unset($validated['videos']);

        try {
            $note = Note::create($validated);

            // Sync videos if provided
            if ($videos !== null) {
                $this->syncVideos($note, $videos);
            }

            // Refresh to get database default values (like status='draft')
            $note->refresh();
            $note->load(['user:id,name,email,avatar', 'videos']);

            return ApiResponse::success([
                'note' => new NoteResource($note)
            ], 'Note created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create note: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, Note $note): JsonResponse
    {

        $user = $request->user();

        // Check if note is a draft
        if ($note->isDraft()) {
            // Draft notes are only visible to owner and admins
            if (!$user || (!$note->isOwnedBy($user) && !in_array($user->role, ['admin', 'superadmin', 'researcher']))) {
                return ApiResponse::forbidden('Draft notes are only visible to their owners');
            }
        } else {
            // Published notes follow privacy rules
            if (!$note->isOwnedBy($user) && !$note->isPublic()) {
                return ApiResponse::forbidden('You can only view your own notes or public notes');
            }
        }

        $with = [
            'user:id,name,email,avatar',
            'comments',
            'videos',
        ];

        // Add user bookmarks only if user is authenticated
        if ($request->user()) {
            $with['userBookmarks'] = function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)->select('id', 'bookmarkable_type', 'bookmarkable_id', 'user_id');
            };
        }

        $note->load($with)->loadCount('bookmarks');

        return ApiResponse::success([
            'note' => new NoteResource($note)
        ], 'Note retrieved successfully');
    }

    public function update(UpdateNoteRequest $request, Note $note): JsonResponse
    {
        $validated = $request->validated();

        // Extract videos from validated data
        $videos = $validated['videos'] ?? null;
        unset($validated['videos']);

        try {
            $note->update($validated);

            // Sync videos if provided (replaces existing videos)
            if ($videos !== null) {
                $this->syncVideos($note, $videos);
            }

            $note->load(['user:id,name,email,avatar', 'videos']);

            return ApiResponse::success([
                'note' => new NoteResource($note)
            ], 'Note updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update note: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Note $note): JsonResponse
    {
        if (!$note->isOwnedBy(auth()->user())) {
            return ApiResponse::forbidden('You can only delete your own notes');
        }

        try {
            $note->delete();

            return ApiResponse::success([], 'Note deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete note: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Sync videos for a note.
     * Deletes all existing videos and creates new ones.
     *
     * @param Note $note
     * @param array $videos
     * @return void
     */
    private function syncVideos(Note $note, array $videos): void
    {
        // Delete existing videos
        $note->videos()->delete();

        // Create new videos with auto-incremented sort_order if not provided
        foreach ($videos as $index => $videoData) {
            $note->videos()->create([
                'video_url' => $videoData['video_url'],
                'thumbnail_url' => $videoData['thumbnail_url'] ?? null,
                'platform' => $videoData['platform'] ?? null,
                'sort_order' => $videoData['sort_order'] ?? $index,
            ]);
        }
    }
}
