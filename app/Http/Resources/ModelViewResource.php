<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModelViewResource extends JsonResource
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
            'viewable_type' => class_basename($this->viewable_type),
            'viewable_id' => $this->viewable_id,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'ip_address' => $this->ip_address,
            'user_agent_hash' => $this->user_agent_hash,
            'user_agent' => $this->user_agent,
            'ip_country' => $this->ip_country,
            'ip_country_code' => $this->ip_country_code,
            'ip_continent' => $this->ip_continent,
            'ip_continent_code' => $this->ip_continent_code,
            'ip_region' => $this->ip_region,
            'ip_city' => $this->ip_city,
            'ip_timezone' => $this->ip_timezone,
            'device_type' => $this->device_type,
            'device_platform' => $this->device_platform,
            'device_browser' => $this->device_browser,
            'viewed_at' => $this->viewed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_bot' => $this->is_bot,
            'bot_name' => $this->bot_name,
            'is_search_engine' => $this->is_search_engine,
            'is_social_media' => $this->is_social_media,
            'user' => $this->when($this->relationLoaded('user') && $this->user, function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'role' => $this->user->role,
                ];
            }),
            'viewable' => $this->when($this->relationLoaded('viewable') && $this->viewable, function () {
                return [
                    'type' => class_basename($this->viewable_type),
                    'id' => $this->viewable_id,
                    'title' => $this->viewable->title ?? $this->viewable->name ?? 'N/A',
                    'slug' => $this->viewable->slug ?? null,
                ];
            }),
        ];
    }
}