<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteListResource extends JsonResource
{
    /**
     * Transform the resource into an array for list endpoints.
     * Excludes the content field for better performance.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isBot = $request->attributes->get('is_bot', false);
        $user = $request->user();
        $hasAccess = $this->resource->userHasAccess($user);
        $isFree = $this->resource->isFree();

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            // In list view, always show preview for paid content, never full content
            'content_preview' => !$isFree ? $this->resource->getContentPreview() : null,
            'is_private' => $this->is_private,
            'tags' => $this->tags ?? [],
            'tags_list' => $this->tags_list ?? '',
            // Pricing fields
            'price_ngn' => $this->price_ngn,
            'price_usd' => $this->price_usd,
            'is_free' => $isFree,
            'is_paid' => !$isFree,
            'has_access' => $hasAccess,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'avatar' => $this->user->avatar,
                    'is_creator' => $this->user->isCreator(),
                ];
            }),
            'comments_count' => $this->commentCount(),
            'views_count' => $this->viewsCount(),
            'is_bookmarked' => $this->isBookmarkedBy($request->user()),
            'bookmark_id' => $this->getBookmarkIdFor($request->user()),
            'bookmarks_count' => $this->bookmarks_count ?? $this->getBookmarksCount(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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