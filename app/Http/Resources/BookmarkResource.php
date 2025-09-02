<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookmarkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get the full model resource data using the appropriate resource class
        $bookmarkableData = $this->getBookmarkableResource();

        return [
            'id' => $this->id,
            'bookmarkable_type' => class_basename($this->bookmarkable_type),
            'bookmarkable_id' => $this->bookmarkable_id,
            'bookmarkable' => $bookmarkableData,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get the appropriate resource for the bookmarkable model
     */
    private function getBookmarkableResource(): array
    {
        if (!$this->bookmarkable) {
            return [];
        }

        switch ($this->bookmarkable_type) {
            case 'App\\Models\\CourtCase':
                return (new CaseResource($this->bookmarkable))->toArray(request());

            case 'App\\Models\\Note':
                return (new NoteResource($this->bookmarkable))->toArray(request());

            case 'App\\Models\\Statute':
                return (new StatuteResource($this->bookmarkable))->toArray(request());

            case 'App\\Models\\StatuteDivision':
                return (new StatuteDivisionResource($this->bookmarkable))->toArray(request());

            case 'App\\Models\\StatuteProvision':
                return (new StatuteProvisionResource($this->bookmarkable))->toArray(request());

            default:
                // Fallback for any other model types
                return [
                    'id' => $this->bookmarkable->id,
                    'type' => class_basename($this->bookmarkable_type),
                ];
        }
    }
}
