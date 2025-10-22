<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
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
            'feedback_text' => $this->feedback_text,
            'page' => $this->page,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'status_color' => $this->status_color,

            // User information
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'role' => $this->user->role,
            ],

            // Content information (if attached to specific content)
            'content' => $this->when($this->content_type, function () {
                return [
                    'type' => $this->content_type_name,
                    'id' => $this->content_id,
                    'title' => $this->content?->title ?? $this->content?->name ?? null,
                ];
            }),

            // Images
            'images' => FeedbackImageResource::collection($this->whenLoaded('images')),

            // Resolution information
            'resolved_by' => $this->when($this->resolved_by, function () {
                return [
                    'id' => $this->resolvedBy->id,
                    'name' => $this->resolvedBy->name,
                    'role' => $this->resolvedBy->role,
                ];
            }),
            'resolved_at' => $this->resolved_at?->toIso8601String(),

            // Issues tracking
            'moved_to_issues' => $this->moved_to_issues,
            'moved_by' => $this->when($this->moved_by, function () {
                return [
                    'id' => $this->movedBy->id,
                    'name' => $this->movedBy->name,
                    'role' => $this->movedBy->role,
                ];
            }),
            'moved_at' => $this->moved_at?->toIso8601String(),

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
