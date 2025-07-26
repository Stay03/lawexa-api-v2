<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileListResource extends JsonResource
{
    /**
     * Transform the resource into a simplified array for list views.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->original_name,
            'size' => $this->size,
            'human_size' => $this->human_size,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'is_image' => $this->is_image,
            'is_document' => $this->is_document,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}