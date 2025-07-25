<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseResource extends JsonResource
{
    /**
     * Whether to use simplified file information
     */
    public static $useSimplifiedFiles = false;

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
            'body' => $this->body,
            'report' => $this->report,
            'course' => $this->course,
            'topic' => $this->topic,
            'tag' => $this->tag,
            'principles' => $this->principles,
            'level' => $this->level,
            'slug' => $this->slug,
            'court' => $this->court,
            'date' => $this->date?->format('Y-m-d'),
            'country' => $this->country,
            'citation' => $this->citation,
            'judges' => $this->judges,
            'judicial_precedent' => $this->judicial_precedent,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'files' => $this->whenLoaded('files', function () {
                if (static::$useSimplifiedFiles) {
                    // Return simplified file information
                    return $this->files->map(function ($file) {
                        return [
                            'id' => $file->id,
                            'name' => $file->original_name,
                            'size' => $file->size,
                            'human_size' => $file->human_size,
                            'mime_type' => $file->mime_type,
                            'extension' => $file->extension,
                            'category' => $file->category,
                            'is_image' => $file->is_image,
                            'is_document' => $file->is_document,
                            'created_at' => $file->created_at?->format('Y-m-d H:i:s'),
                        ];
                    });
                }
                
                return FileResource::collection($this->files);
            }),
            'files_count' => $this->when($this->relationLoaded('files'), $this->files->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}