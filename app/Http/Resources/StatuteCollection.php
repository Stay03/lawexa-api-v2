<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StatuteCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        // Map each statute manually with simplified files
        $statutes = $this->collection->map(function ($statute) use ($request) {
            $statuteResource = new StatuteResource($statute);
            StatuteResource::$useSimplifiedFiles = true;
            $data = $statuteResource->toArray($request);
            StatuteResource::$useSimplifiedFiles = false;
            return $data;
        });
        
        $result = [
            'statutes' => $statutes,
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