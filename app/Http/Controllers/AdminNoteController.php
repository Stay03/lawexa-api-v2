<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminCreateNoteRequest;
use App\Http\Requests\AdminUpdateNoteRequest;
use App\Http\Resources\NoteCollection;
use App\Http\Resources\NoteResource;
use App\Http\Responses\ApiResponse;
use App\Models\Note;
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

        try {
            $note = Note::create($validated);
            $note->load('user:id,name,email');

            return ApiResponse::created(
                new NoteResource($note),
                'Note created successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create note: ' . $e->getMessage(), 500);
        }
    }

    public function show(Note $note): JsonResponse
    {
        $note->load('user:id,name,email');
        
        return ApiResponse::success(
            new NoteResource($note),
            'Note retrieved successfully'
        );
    }

    public function update(AdminUpdateNoteRequest $request, Note $note): JsonResponse
    {
        if ($request->user()->isResearcher()) {
            return ApiResponse::forbidden('Researchers cannot edit notes');
        }

        $validated = $request->validated();

        try {
            $note->update($validated);
            $note->load('user:id,name,email');

            return ApiResponse::success(
                new NoteResource($note),
                'Note updated successfully'
            );
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
}
