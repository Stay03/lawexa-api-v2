<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminIssueResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'severity' => $this->severity,
            'priority' => $this->priority,
            'status' => $this->status,
            'area' => $this->area,
            'category' => $this->category,
            'browser_info' => $this->browser_info,
            'environment_info' => $this->environment_info,
            'steps_to_reproduce' => $this->steps_to_reproduce,
            'expected_behavior' => $this->expected_behavior,
            'actual_behavior' => $this->actual_behavior,
            'user' => new UserResource($this->whenLoaded('user')),
            'assigned_to' => new UserResource($this->whenLoaded('assignedTo')),
            'from_feedback' => $this->feedback_id !== null,
            'feedback' => $this->when($this->feedback_id, function() {
                if ($this->relationLoaded('feedback')) {
                    return new FeedbackResource($this->feedback);
                }
                return [
                    'id' => $this->feedback_id,
                ];
            }),
            'files' => FileResource::collection($this->whenLoaded('files')),
            'screenshots' => FileResource::collection($this->whenLoaded('screenshots')),
            'comments_count' => $this->when(isset($this->comments_count), $this->comments_count),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'ai_analysis' => $this->ai_analysis,
            'admin_notes' => $this->admin_notes,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
