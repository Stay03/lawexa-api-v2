<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class IssueCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Transform each issue with IssueResource
        $issues = $this->collection->map(function ($issue) use ($request) {
            return (new IssueResource($issue))->toArray($request);
        });
        
        $result = [
            'data' => $issues,
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
                'filters_applied' => $request->only(['status', 'severity', 'area', 'type']),
            ],
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
        ];
        
        return $result;
    }
}
