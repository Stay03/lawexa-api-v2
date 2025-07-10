<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'error_code' => $this->resource['error_code'] ?? 'GENERAL_ERROR',
            'error_message' => $this->resource['error_message'] ?? 'An error occurred',
            'details' => $this->resource['details'] ?? null,
            'timestamp' => now()->toISOString(),
        ];
    }
}