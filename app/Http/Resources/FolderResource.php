<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'sort_order' => $this->sort_order,
            'is_root' => $this->isRoot(),
            'has_children' => $this->hasChildren(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar,
                ];
            }),

            'parent' => $this->whenLoaded('parent', function () {
                return new FolderResource($this->parent);
            }),

            'children' => FolderResource::collection($this->whenLoaded('children')),

            'items_count' => $this->whenCounted('items'),

            'ancestors' => $this->when($this->resource->relationLoaded('parent'), function () {
                return array_map(function ($ancestor) {
                    return [
                        'id' => $ancestor->id,
                        'name' => $ancestor->name,
                        'slug' => $ancestor->slug,
                    ];
                }, $this->getAncestors());
            }),

            'views_count' => $this->viewsCount(),
            'is_bookmarked' => $this->isBookmarkedBy($request->user()),
            'bookmarks_count' => $this->bookmarks_count ?? $this->getBookmarksCount(),
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

        if (method_exists($item, 'getRouteKeyName') && $item->getRouteKeyName() === 'slug') {
            $baseData['slug'] = $item->slug;
        }

        if (isset($item->is_private)) {
            $baseData['is_private'] = $item->is_private;
        }

        if (isset($item->is_public)) {
            $baseData['is_public'] = $item->is_public;
        }

        return $baseData;
    }
}
