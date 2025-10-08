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

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'is_private' => $this->is_private,
            'tags' => $this->tags ?? [],
            'tags_list' => $this->tags_list ?? '',
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'avatar' => $this->user->avatar,
                ];
            }),
            'comments_count' => $this->commentCount(),
            'views_count' => $this->viewsCount(),
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