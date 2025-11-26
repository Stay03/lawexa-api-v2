<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminCreateNoteRequest;
use App\Http\Requests\AdminUpdateNoteRequest;
use App\Http\Resources\NoteCollection;
use App\Http\Resources\NoteResource;
use App\Http\Responses\ApiResponse;
use App\Models\Note;
use App\Models\NoteVideo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Note::with('user:id,name,email');

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('tag')) {
            $query->withTag($request->tag);
        }

        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
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

        $noteCollection = new NoteCollection($notes);
        
        return ApiResponse::success(
            $noteCollection->toArray($request),
            'Notes retrieved successfully'
        );
    }

    public function store(AdminCreateNoteRequest $request): JsonResponse
    {
        if ($request->user()->isResearcher()) {
            return ApiResponse::forbidden('Researchers cannot create notes');
        }

        $validated = $request->validated();

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
            $note->load(['user:id,name,email', 'videos']);

            return ApiResponse::success([
                'note' => new NoteResource($note)
            ], 'Note created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create note: ' . $e->getMessage(), 500);
        }
    }

    public function show(Note $note): JsonResponse
    {
        $note->load(['user:id,name,email', 'videos']);

        return ApiResponse::success([
            'note' => new NoteResource($note)
        ], 'Note retrieved successfully');
    }

    public function update(AdminUpdateNoteRequest $request, Note $note): JsonResponse
    {
        if ($request->user()->isResearcher()) {
            return ApiResponse::forbidden('Researchers cannot edit notes');
        }

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

            $note->load(['user:id,name,email', 'videos']);

            return ApiResponse::success([
                'note' => new NoteResource($note)
            ], 'Note updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update note: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Note $note): JsonResponse
    {
        if (request()->user()->isResearcher()) {
            return ApiResponse::forbidden('Researchers cannot delete notes');
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
