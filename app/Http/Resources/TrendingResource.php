<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CourtCase;
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use App\Models\Note;
use App\Models\Folder;
use App\Models\Comment;

class TrendingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get content-specific data first
        $contentData = $this->getContentSpecificData($request);
        
        // Add trending-specific fields
        $contentData['content_type'] = $this->content_type ?? $this->getContentType();
        $contentData['trending_metrics'] = [
            'trending_score' => $this->trending_score ?? 0,
            'total_views' => $this->total_views ?? 0,
            'unique_viewers' => $this->unique_viewers ?? 0,
            'weighted_score' => $this->weighted_score ?? 0,
            'latest_view' => $this->latest_view ?? null,
            'earliest_view' => $this->earliest_view ?? null,
        ];
        
        return $contentData;
    }

    private function getContentType(): string
    {
        // Get the actual model class, not the resource class
        $modelClass = get_class($this->resource->resource ?? $this->resource);
        
        return match ($modelClass) {
            CourtCase::class => 'cases',
            Statute::class => 'statutes',
            StatuteDivision::class => 'divisions',
            StatuteProvision::class => 'provisions',
            Note::class => 'notes',
            Folder::class => 'folders',
            Comment::class => 'comments',
            default => 'unknown',
        };
    }

    private function getContentSpecificData(Request $request): array
    {
        // Get the actual model class, not the resource class
        $modelClass = get_class($this->resource->resource ?? $this->resource);
        
        return match ($modelClass) {
            CourtCase::class => $this->getCaseData($request),
            Statute::class => $this->getStatuteData($request),
            StatuteDivision::class => $this->getDivisionData($request),
            StatuteProvision::class => $this->getProvisionData($request),
            Note::class => $this->getNoteData($request),
            Folder::class => $this->getFolderData($request),
            Comment::class => $this->getCommentData($request),
            default => [],
        };
    }

    private function getCaseData(Request $request): array
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
                return $this->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->file_name,
                        'file_size' => $file->file_size,
                    ];
                });
            }),
        ];
    }

    private function getStatuteData(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'country' => $this->country,
            'year' => $this->year,
            'description' => $this->description,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'files' => $this->whenLoaded('files', function () {
                return $this->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->file_name,
                        'file_size' => $file->file_size,
                    ];
                });
            }),
        ];
    }

    private function getDivisionData(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'division_number' => $this->division_number,
            'content' => $this->content,
            'statute' => $this->whenLoaded('statute', function () {
                return [
                    'id' => $this->statute->id,
                    'title' => $this->statute->title,
                    'slug' => $this->statute->slug,
                ];
            }),
        ];
    }

    private function getProvisionData(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'provision_number' => $this->provision_number,
            'content' => $this->content,
            'division' => $this->whenLoaded('division', function () {
                return [
                    'id' => $this->division->id,
                    'title' => $this->division->title,
                    'slug' => $this->division->slug,
                ];
            }),
            'statute' => $this->whenLoaded('statute', function () {
                return [
                    'id' => $this->statute->id,
                    'title' => $this->statute->title,
                    'slug' => $this->statute->slug,
                ];
            }),
        ];
    }

    private function getNoteData(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'tags' => $this->tags,
            'is_private' => $this->is_private,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'files' => $this->whenLoaded('files', function () {
                return $this->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->file_name,
                        'file_size' => $file->file_size,
                    ];
                });
            }),
        ];
    }

    private function getFolderData(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'items_count' => $this->items_count ?? 0,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
        ];
    }

    private function getCommentData(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'commentable' => $this->whenLoaded('commentable', function () {
                // Return basic info about what this comment is on
                return [
                    'type' => class_basename($this->commentable_type),
                    'id' => $this->commentable_id,
                    'title' => $this->commentable->title ?? $this->commentable->name ?? 'N/A',
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}