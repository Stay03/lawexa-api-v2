<?php

namespace App\Http\Resources;

use App\Models\ModelView;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class SearchHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get sample views for this search query
        $sampleViews = ModelView::where('search_query', $this->search_query)
            ->where('is_from_search', true)
            ->when($request->user(), fn($q) => $q->where('user_id', $request->user()->id))
            ->with('viewable')
            ->orderByDesc('viewed_at')
            ->limit(3)
            ->get()
            ->map(function ($view) {
                return [
                    'id' => $view->id,
                    'type' => $this->mapClassToContentType($view->viewable_type),
                    'title' => $view->viewable?->title ?? $view->viewable?->case_name ?? 'Unknown',
                    'slug' => $view->viewable?->slug ?? null,
                    'viewed_at' => $view->viewed_at->toISOString(),
                ];
            });

        // Get content type breakdown for this search query
        $contentTypes = ModelView::where('search_query', $this->search_query)
            ->where('is_from_search', true)
            ->when($request->user(), fn($q) => $q->where('user_id', $request->user()->id))
            ->select('viewable_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('viewable_type')
            ->get()
            ->mapWithKeys(function ($item) {
                $type = $this->mapClassToContentType($item->viewable_type);
                return [$type => $item->count];
            });

        return [
            'search_query' => $this->search_query,
            'views_count' => $this->views_count,
            'unique_content_count' => $this->unique_content_count,
            'content_types' => $contentTypes,
            'first_searched_at' => $this->first_searched_at,
            'last_searched_at' => $this->last_searched_at,
            'sample_views' => $sampleViews,
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
}
