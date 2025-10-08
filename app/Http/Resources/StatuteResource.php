<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatuteResource extends JsonResource
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
        $isBot = $request->attributes->get('is_bot', false);
        
        $data = [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'short_title' => $this->short_title,
            'year_enacted' => $this->year_enacted,
            'commencement_date' => $this->commencement_date?->format('Y-m-d'),
            'status' => $this->status,
            'repealed_date' => $this->repealed_date?->format('Y-m-d'),
            'jurisdiction' => $this->jurisdiction,
            'country' => $this->country,
            'state' => $this->state,
            'local_government' => $this->local_government,
            'citation_format' => $this->citation_format,
            'sector' => $this->sector,
            'tags' => $this->tags,
            'description' => $this->description,
            'range' => $this->range,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Relationships
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            
            'parent_statute' => $this->whenLoaded('parentStatute', function () {
                return $this->parentStatute ? [
                    'id' => $this->parentStatute->id,
                    'title' => $this->parentStatute->title,
                    'slug' => $this->parentStatute->slug
                ] : null;
            }),
            
            'child_statutes' => $this->whenLoaded('childStatutes', function () {
                return $this->childStatutes->map(function ($statute) {
                    return [
                        'id' => $statute->id,
                        'title' => $statute->title,
                        'slug' => $statute->slug
                    ];
                });
            }),
            
            'repealing_statute' => $this->whenLoaded('repealingStatute', function () {
                return $this->repealingStatute ? [
                    'id' => $this->repealingStatute->id,
                    'title' => $this->repealingStatute->title,
                    'slug' => $this->repealingStatute->slug
                ] : null;
            }),
            
            'amendments' => $this->whenLoaded('amendments', function () {
                return $this->amendments->map(function ($amendment) {
                    return [
                        'id' => $amendment->id,
                        'title' => $amendment->title,
                        'slug' => $amendment->slug,
                        'effective_date' => $amendment->pivot->effective_date,
                        'description' => $amendment->pivot->amendment_description
                    ];
                });
            }),
            
            'amended_by' => $this->whenLoaded('amendedBy', function () {
                return $this->amendedBy->map(function ($statute) {
                    return [
                        'id' => $statute->id,
                        'title' => $statute->title,
                        'slug' => $statute->slug,
                        'effective_date' => $statute->pivot->effective_date,
                        'description' => $statute->pivot->amendment_description
                    ];
                });
            }),
            
            'cited_statutes' => $this->whenLoaded('citedStatutes', function () {
                return $this->citedStatutes->map(function ($statute) {
                    return [
                        'id' => $statute->id,
                        'title' => $statute->title,
                        'slug' => $statute->slug,
                        'context' => $statute->pivot->citation_context
                    ];
                });
            }),
            
            'citing_statutes' => $this->whenLoaded('citingStatutes', function () {
                return $this->citingStatutes->map(function ($statute) {
                    return [
                        'id' => $statute->id,
                        'title' => $statute->title,
                        'slug' => $statute->slug,
                        'context' => $statute->pivot->citation_context
                    ];
                });
            }),
            
            'divisions' => $this->whenLoaded('divisions', function () {
                return StatuteDivisionResource::collection($this->divisions);
            }),
            
            'divisions_count' => $this->when($this->relationLoaded('divisions'), $this->divisions->count()),
            
            'provisions' => $this->whenLoaded('provisions', function () {
                return StatuteProvisionResource::collection($this->provisions);
            }),
            
            'provisions_count' => $this->when($this->relationLoaded('provisions'), $this->provisions->count()),
            
            'schedules' => $this->whenLoaded('schedules', function () {
                return StatuteScheduleResource::collection($this->schedules);
            }),
            
            'schedules_count' => $this->when($this->relationLoaded('schedules'), $this->schedules->count()),
            
            'files' => $this->whenLoaded('files', function () {
                if (static::$useSimplifiedFiles) {
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
            'views_count' => $this->viewsCount(),
            'is_bookmarked' => $this->isBookmarkedBy($request->user()),
            'bookmarks_count' => $this->bookmarks_count ?? $this->getBookmarksCount(),
        ];

        // Add bot indicator if it's a bot request
        if ($isBot) {
            $data['isBot'] = true;
            $botInfo = $request->attributes->get('bot_info', []);
            if (!empty($botInfo['bot_name'])) {
                $data['bot_info'] = [
                    'bot_name' => $botInfo['bot_name'],
                    'is_search_engine' => $botInfo['is_search_engine'] ?? false,
                    'is_social_media' => $botInfo['is_social_media'] ?? false,
                ];
            }
        }

        return $data;
    }
}