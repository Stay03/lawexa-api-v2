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
            'is_bookmarked' => $this->isBookmarkedBy($request->user()),
            'bookmark_id' => $this->getBookmarkIdFor($request->user()),
            'bookmarks_count' => $this->bookmarks_count ?? $this->getBookmarksCount(),
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
            'is_bookmarked' => $this->isBookmarkedBy($request->user()),
            'bookmark_id' => $this->getBookmarkIdFor($request->user()),
            'bookmarks_count' => $this->bookmarks_count ?? $this->getBookmarksCount(),
        ];
    }

    private function getDivisionData(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->division_title,
            'slug' => $this->slug,
            'division_number' => $this->division_number,
            'division_type' => $this->division_type,
            'division_subtitle' => $this->division_subtitle,
            'range' => $this->range,
            'content' => $this->content,
            'statute' => $this->whenLoaded('statute', function () {
                return [
                    'id' => $this->statute->id,
                    'title' => $this->statute->title,
                    'slug' => $this->statute->slug,
                ];
            }),
            'path' => $this->buildDivisionPath($this->resource),
            'immediate_parent' => $this->whenLoaded('parentDivision', function () {
                if (!$this->parentDivision) {
                    return null;
                }
                return [
                    'id' => $this->parentDivision->id,
                    'type' => 'division',
                    'title' => $this->parentDivision->division_title,
                    'number' => $this->parentDivision->division_number,
                    'structural_type' => $this->parentDivision->division_type,
                    'slug' => $this->parentDivision->slug,
                ];
            }),
            'is_bookmarked' => $this->isBookmarkedBy($request->user()),
            'bookmark_id' => $this->getBookmarkIdFor($request->user()),
            'bookmarks_count' => $this->bookmarks_count ?? $this->getBookmarksCount(),
        ];
    }

    private function getProvisionData(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->provision_title,
            'slug' => $this->slug,
            'provision_number' => $this->provision_number,
            'provision_type' => $this->provision_type,
            'provision_text' => $this->provision_text,
            'marginal_note' => $this->marginal_note,
            'content' => $this->content,
            'division' => $this->whenLoaded('division', function () {
                return [
                    'id' => $this->division->id,
                    'title' => $this->division->division_title,
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
            'path' => $this->buildProvisionPath($this->resource),
            'immediate_parent' => $this->getProvisionImmediateParent(),
            'is_bookmarked' => $this->isBookmarkedBy($request->user()),
            'bookmark_id' => $this->getBookmarkIdFor($request->user()),
            'bookmarks_count' => $this->bookmarks_count ?? $this->getBookmarksCount(),
        ];
    }

    private function getNoteData(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'tags' => $this->tags,
            'is_private' => $this->is_private,
            'comments_count' => $this->commentCount(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar,
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
            'is_bookmarked' => $this->isBookmarkedBy($request->user()),
            'bookmark_id' => $this->getBookmarkIdFor($request->user()),
            'bookmarks_count' => $this->bookmarks_count ?? $this->getBookmarksCount(),
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
            'is_bookmarked' => $this->isBookmarkedBy($request->user()),
            'bookmark_id' => $this->getBookmarkIdFor($request->user()),
            'bookmarks_count' => $this->bookmarks_count ?? $this->getBookmarksCount(),
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

    /**
     * Build hierarchical path for a division by traversing parent relationships
     */
    private function buildDivisionPath($division): array
    {
        $path = [];
        $current = $division;

        // Traverse up the parent chain
        while ($current && isset($current->parent_division_id)) {
            $parent = $current->parentDivision;
            if (!$parent) {
                break;
            }

            array_unshift($path, $this->formatPathNode($parent, 'division'));
            $current = $parent;
        }

        return $path;
    }

    /**
     * Build hierarchical path for a provision by traversing parent relationships
     */
    private function buildProvisionPath($provision): array
    {
        $path = [];

        // First, add all parent provisions
        $current = $provision;
        while ($current && isset($current->parent_provision_id)) {
            $parent = $current->parentProvision;
            if (!$parent) {
                break;
            }

            array_unshift($path, $this->formatPathNode($parent, 'provision'));
            $current = $parent;
        }

        // Then, add all parent divisions (if the provision or its root parent has a division)
        $divisionToCheck = $current->division ?? $provision->division;
        if ($divisionToCheck) {
            $divisionPath = $this->buildDivisionPath($divisionToCheck);
            // Add the immediate division to the path
            $divisionPath[] = $this->formatPathNode($divisionToCheck, 'division');
            // Merge division path before provision path
            $path = array_merge($divisionPath, $path);
        }

        return $path;
    }

    /**
     * Format a path node consistently for both divisions and provisions
     */
    private function formatPathNode($item, string $type): array
    {
        if ($type === 'division') {
            return [
                'id' => $item->id,
                'type' => 'division',
                'title' => $item->division_title,
                'number' => $item->division_number,
                'structural_type' => $item->division_type,
                'slug' => $item->slug,
            ];
        } else {
            return [
                'id' => $item->id,
                'type' => 'provision',
                'title' => $item->provision_title,
                'number' => $item->provision_number,
                'structural_type' => $item->provision_type,
                'slug' => $item->slug,
            ];
        }
    }

    /**
     * Get immediate parent for a provision (could be another provision or a division)
     */
    private function getProvisionImmediateParent(): ?array
    {
        // Check if there's a parent provision first
        if ($this->resource->parent_provision_id && $this->relationLoaded('parentProvision') && $this->parentProvision) {
            return [
                'id' => $this->parentProvision->id,
                'type' => 'provision',
                'title' => $this->parentProvision->provision_title,
                'number' => $this->parentProvision->provision_number,
                'structural_type' => $this->parentProvision->provision_type,
                'slug' => $this->parentProvision->slug,
            ];
        }

        // Otherwise, check if there's a division parent
        if ($this->resource->division_id && $this->relationLoaded('division') && $this->division) {
            return [
                'id' => $this->division->id,
                'type' => 'division',
                'title' => $this->division->division_title,
                'number' => $this->division->division_number,
                'structural_type' => $this->division->division_type,
                'slug' => $this->division->slug,
            ];
        }

        return null;
    }
}