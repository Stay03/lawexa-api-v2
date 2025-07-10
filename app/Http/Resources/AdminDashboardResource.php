<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'role' => $this->resource['role'],
            'dashboard_data' => $this->resource['data'],
            'permissions' => $this->resource['data']['permissions'] ?? [],
            'user_access_level' => $this->getUserAccessLevel($this->resource['role']),
        ];
    }

    private function getUserAccessLevel(string $role): string
    {
        return match ($role) {
            'admin' => 'Basic administrative access',
            'researcher' => 'Extended analytics and research access',
            'superadmin' => 'Full system access and configuration',
            default => 'User access'
        };
    }
}