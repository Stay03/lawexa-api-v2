<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
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
            'name' => $this->original_name,
            'filename' => $this->filename,
            'size' => $this->size,
            'human_size' => $this->human_size,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'category' => $this->category,
            'url' => $this->url,
            'download_url' => $this->download_url,
            'is_image' => $this->is_image,
            'is_document' => $this->is_document,
            'disk' => $this->disk,
            'metadata' => $this->when($this->metadata, $this->metadata),
            
            // File dimensions (for images)
            'width' => $this->when(
                $this->is_image && isset($this->metadata['width']), 
                $this->metadata['width'] ?? null
            ),
            'height' => $this->when(
                $this->is_image && isset($this->metadata['height']), 
                $this->metadata['height'] ?? null
            ),
            
            // Parent model information (if needed)
            'attached_to' => $this->when($this->fileable_id && $this->fileable_type, [
                'type' => $this->fileable_type,
                'id' => $this->fileable_id,
            ]),
            
            // Upload information
            'uploaded_by' => $this->when($this->uploaded_by, [
                'id' => $this->uploadedBy?->id,
                'name' => $this->uploadedBy?->name,
                'email' => $this->uploadedBy?->email,
            ]),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'exists_in_storage' => $this->resource->existsInStorage(),
            ],
        ];
    }
}