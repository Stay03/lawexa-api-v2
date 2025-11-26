<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteVideoResource extends JsonResource
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
            'video_url' => $this->video_url,
            'thumbnail_url' => $this->thumbnail_url,
            'platform' => $this->platform,
            'sort_order' => $this->sort_order,
        ];
    }
}
