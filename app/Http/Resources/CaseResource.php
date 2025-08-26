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
            'case_report_text' => $this->whenLoaded('caseReport', function () {
                return $this->caseReport?->full_report_text;
            }),
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
            'views_count' => $this->viewsCount(),
            'similar_cases' => $this->when(
                $this->relationLoaded('similarCases') || $this->relationLoaded('casesWhereThisIsSimilar'),
                function () {
                    $similarCases = collect();
                    
                    // Add cases this case is similar to
                    if ($this->relationLoaded('similarCases')) {
                        $similarCases = $similarCases->merge($this->similarCases);
                    }
                    
                    // Add cases where this case is marked as similar
                    if ($this->relationLoaded('casesWhereThisIsSimilar')) {
                        $similarCases = $similarCases->merge($this->casesWhereThisIsSimilar);
                    }
                    
                    // Remove duplicates and map to desired format
                    return $similarCases->unique('id')->map(function ($similarCase) {
                        return [
                            'id' => $similarCase->id,
                            'title' => $similarCase->title,
                            'slug' => $similarCase->slug,
                            'court' => $similarCase->court,
                            'date' => $similarCase->date?->format('Y-m-d'),
                            'country' => $similarCase->country,
                            'citation' => $similarCase->citation,
                        ];
                    })->values();
                }
            ),
            'similar_cases_count' => $this->when(
                $this->relationLoaded('similarCases') || $this->relationLoaded('casesWhereThisIsSimilar'),
                function () {
                    $count = 0;
                    if ($this->relationLoaded('similarCases')) {
                        $count += $this->similarCases->count();
                    }
                    if ($this->relationLoaded('casesWhereThisIsSimilar')) {
                        $count += $this->casesWhereThisIsSimilar->count();
                    }
                    return $count;
                }
            ),
            'cited_cases' => $this->when(
                $this->relationLoaded('citedCases') || $this->relationLoaded('casesThatCiteThis'),
                function () {
                    $citedCases = collect();
                    
                    // Add cases this case cites
                    if ($this->relationLoaded('citedCases')) {
                        $citedCases = $citedCases->merge($this->citedCases);
                    }
                    
                    // Add cases that cite this case
                    if ($this->relationLoaded('casesThatCiteThis')) {
                        $citedCases = $citedCases->merge($this->casesThatCiteThis);
                    }
                    
                    // Remove duplicates and map to desired format
                    return $citedCases->unique('id')->map(function ($citedCase) {
                        return [
                            'id' => $citedCase->id,
                            'title' => $citedCase->title,
                            'slug' => $citedCase->slug,
                            'court' => $citedCase->court,
                            'date' => $citedCase->date?->format('Y-m-d'),
                            'country' => $citedCase->country,
                            'citation' => $citedCase->citation,
                        ];
                    })->values();
                }
            ),
            'cited_cases_count' => $this->when(
                $this->relationLoaded('citedCases') || $this->relationLoaded('casesThatCiteThis'),
                function () {
                    $count = 0;
                    if ($this->relationLoaded('citedCases')) {
                        $count += $this->citedCases->count();
                    }
                    if ($this->relationLoaded('casesThatCiteThis')) {
                        $count += $this->casesThatCiteThis->count();
                    }
                    return $count;
                }
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}