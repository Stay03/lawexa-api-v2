<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchViewResource extends JsonResource
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
            'search_query' => $this->search_query,
            'viewed_at' => $this->viewed_at->toISOString(),

            // Viewed content details
            'content' => $this->when($this->relationLoaded('viewable') && $this->viewable, function () {
                return [
                    'type' => $this->mapClassToContentType($this->viewable_type),
                    'id' => $this->viewable_id,
                    'title' => $this->viewable->title ?? $this->viewable->case_name ?? 'Unknown',
                    'slug' => $this->viewable->slug ?? null,
                    'url' => $this->getContentUrl(),
                ];
            }),

            // User details (if loaded)
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),

            // Location details
            'location' => $this->when($this->ip_country || $this->ip_city, function () {
                return [
                    'country' => $this->ip_country,
                    'country_code' => $this->ip_country_code,
                    'region' => $this->ip_region,
                    'city' => $this->ip_city,
                    'timezone' => $this->ip_timezone,
                ];
            }),

            // Device details
            'device' => $this->when($this->device_type || $this->device_platform, function () {
                return [
                    'type' => $this->device_type,
                    'platform' => $this->device_platform,
                    'browser' => $this->device_browser,
                ];
            }),
        ];
    }

    /**
     * Map full class name to content type string.
     *
     * @param string $className
     * @return string
     */
    private function mapClassToContentType(string $className): string
    {
        return match($className) {
            'App\\Models\\CourtCase' => 'case',
            'App\\Models\\Statute' => 'statute',
            'App\\Models\\Division' => 'division',
            'App\\Models\\Provision' => 'provision',
            'App\\Models\\Schedule' => 'schedule',
            'App\\Models\\Note' => 'note',
            'App\\Models\\Folder' => 'folder',
            'App\\Models\\Comment' => 'comment',
            default => 'unknown',
        };
    }

    /**
     * Get the URL for the viewed content.
     *
     * @return string
     */
    private function getContentUrl(): string
    {
        $type = $this->mapClassToContentType($this->viewable_type);
        $slug = $this->viewable->slug ?? $this->viewable_id;

        return match($type) {
            'case' => "/cases/{$slug}",
            'statute' => "/statutes/{$slug}",
            'division' => "/statutes/{$this->viewable->statute->slug}/divisions/{$slug}",
            'provision' => "/statutes/{$this->viewable->statute->slug}/provisions/{$slug}",
            'schedule' => "/statutes/{$this->viewable->statute->slug}/schedules/{$slug}",
            'note' => "/notes/{$slug}",
            'folder' => "/folders/{$slug}",
            'comment' => "/comments/{$this->viewable_id}",
            default => "#",
        };
    }
}
