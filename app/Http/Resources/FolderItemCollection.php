<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FolderItemCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($item) {
                return [
                    'id' => $item->id,
                    'folder_id' => $item->folder_id,
                    'folderable_type' => $item->folderable_type,
                    'folderable_id' => $item->folderable_id,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'folderable' => $this->formatFolderableItem($item->folderable),
                ];
            }),
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ],
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
        ];
    }

    private function formatFolderableItem($item): array
    {
        if (! $item) {
            return [];
        }

        $baseData = [
            'id' => $item->id,
            'title' => $item->title ?? $item->name ?? $item->provision_title ?? $item->division_title,
            'type' => class_basename($item),
        ];

        // Add specific fields based on item type
        if (class_basename($item) === 'CourtCase') {
            $baseData['body'] = $item->body;
            $baseData['topic'] = $item->topic;
            $baseData['level'] = $item->level;
            $baseData['slug'] = $item->slug;
            $baseData['court'] = $item->court;
            $baseData['date'] = $item->date;
            $baseData['country'] = $item->country;
            $baseData['citation'] = $item->citation;
            $baseData['created_by'] = $item->created_by;
        } elseif (class_basename($item) === 'Note') {
            $baseData['content'] = $item->content;
            $baseData['user_id'] = $item->user_id;
            $baseData['is_private'] = $item->is_private;
            $baseData['tags'] = $item->tags;
        } elseif (class_basename($item) === 'Statute') {
            $baseData['short_title'] = $item->short_title;
            $baseData['year_enacted'] = $item->year_enacted;
            $baseData['status'] = $item->status;
            $baseData['jurisdiction'] = $item->jurisdiction;
            $baseData['country'] = $item->country;
            $baseData['slug'] = $item->slug;
        }

        // Add common fields
        $baseData['created_at'] = $item->created_at;
        $baseData['updated_at'] = $item->updated_at;

        return $baseData;
    }
}
