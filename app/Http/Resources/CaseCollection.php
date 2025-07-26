<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CaseCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        // Map each case manually with simplified files
        $cases = $this->collection->map(function ($case) use ($request) {
            $caseResource = new CaseResource($case);
            CaseResource::$useSimplifiedFiles = true;
            $data = $caseResource->toArray($request);
            CaseResource::$useSimplifiedFiles = false;
            return $data;
        });
        
        $result = [
            'cases' => $cases,
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
        
        return $result;
    }
}