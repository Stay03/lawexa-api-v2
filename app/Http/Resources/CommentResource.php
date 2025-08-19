<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'content' => $this->content,
            'is_approved' => $this->is_approved,
            'is_edited' => $this->is_edited,
            'edited_at' => $this->edited_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ],
            'commentable' => $this->when($this->relationLoaded('commentable'), function () {
                return [
                    'type' => class_basename($this->commentable_type),
                    'id' => $this->commentable_id,
                ];
            }),
            'parent_id' => $this->parent_id,
            'replies_count' => $this->whenLoaded('replies', function () {
                return $this->replies->count();
            }),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
        ];
    }
}
