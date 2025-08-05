<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatuteScheduleResource extends JsonResource
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
            'slug' => $this->slug,
            'statute_id' => $this->statute_id,
            'schedule_number' => $this->schedule_number,
            'schedule_title' => $this->schedule_title,
            'content' => $this->content,
            'schedule_type' => $this->schedule_type,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'effective_date' => $this->effective_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}