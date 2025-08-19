<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Http\Resources\NoteCollection;
use App\Http\Resources\NoteResource;
use App\Http\Responses\ApiResponse;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Note::with(['user:id,name,email', 'comments'])
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

        $notes = $query->orderByLatest()
                      ->paginate($request->get('per_page', 15));

        $noteCollection = new NoteCollection($notes);
        
        return ApiResponse::success(
            $noteCollection->toArray($request),
            'Notes retrieved successfully'
        );
    }

    public function store(CreateNoteRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        try {
            $note = Note::create($validated);
            $note->load('user:id,name,email');

            return ApiResponse::success([
                'note' => new NoteResource($note)
            ], 'Note created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create note: ' . $e->getMessage(), 500);
        }
    }

    public function show(Note $note): JsonResponse
    {
        if (!$note->isOwnedBy(auth()->user())) {
            return ApiResponse::forbidden('You can only view your own notes');
        }

        $note->load(['user:id,name,email', 'comments']);
        
        return ApiResponse::success([
            'note' => new NoteResource($note)
        ], 'Note retrieved successfully');
    }

    public function update(UpdateNoteRequest $request, Note $note): JsonResponse
    {
        $validated = $request->validated();

        try {
            $note->update($validated);
            $note->load('user:id,name,email');

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
}
