<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_name' => $this->type_name,
            'title' => $this->title,
            'additional_notes' => $this->additional_notes,
            'status' => $this->status,
            'status_name' => $this->status_name,

            // User who made the request
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'role' => $this->user->role,
                    'avatar' => $this->user->avatar,
                ];
            }),

            // Created content (when fulfilled)
            'created_content' => $this->when(
                $this->isFulfilled() && $this->relationLoaded('createdContent'),
                function () {
                    return match($this->created_content_type) {
                        'App\Models\CourtCase' => new CaseResource($this->createdContent),
                        'App\Models\Statute' => new StatuteResource($this->createdContent),
                        'App\Models\StatuteProvision' => new StatuteProvisionResource($this->createdContent),
                        'App\Models\StatuteDivision' => new StatuteDivisionResource($this->createdContent),
                        default => null,
                    };
                }
            ),

            // Related statute (for provision/division requests)
            'statute' => new StatuteResource($this->whenLoaded('statute')),
            'parent_division' => new StatuteDivisionResource($this->whenLoaded('parentDivision')),
            'parent_provision' => new StatuteProvisionResource($this->whenLoaded('parentProvision')),

            // Fulfillment info
            'fulfilled_by' => $this->whenLoaded('fulfilledBy', function () {
                return [
                    'id' => $this->fulfilledBy->id,
                    'name' => $this->fulfilledBy->name,
                    'role' => $this->fulfilledBy->role,
                    'avatar' => $this->fulfilledBy->avatar,
                ];
            }),
            'fulfilled_at' => $this->fulfilled_at?->toISOString(),

            // Rejection info
            'rejected_by' => $this->whenLoaded('rejectedBy', function () {
                return [
                    'id' => $this->rejectedBy->id,
                    'name' => $this->rejectedBy->name,
                    'role' => $this->rejectedBy->role,
                    'avatar' => $this->rejectedBy->avatar,
                ];
            }),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'rejection_reason' => $this->when(
                $this->isRejected() && ($this->user_id === $request->user()?->id || $request->user()?->hasAdminAccess()),
                $this->rejection_reason
            ),

            // Duplicate count (admin only)
            'duplicate_count' => $this->when(
                $request->user()?->hasAdminAccess(),
                fn() => $this->getDuplicateCount()
            ),

            // Permissions
            'can_edit' => $this->canBeEditedByUser(),
            'can_delete' => $this->canBeDeletedByUser(),

            // Comments (if loaded)
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->when(
                isset($this->comments_count),
                $this->comments_count
            ),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
