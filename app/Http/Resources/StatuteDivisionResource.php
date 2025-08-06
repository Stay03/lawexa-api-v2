<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatuteDivisionResource extends JsonResource
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
            'slug' => $this->slug,
            'statute_id' => $this->statute_id,
            'parent_division_id' => $this->parent_division_id,
            'division_type' => $this->division_type,
            'division_number' => $this->division_number,
            'division_title' => $this->division_title,
            'division_subtitle' => $this->division_subtitle,
            'content' => $this->content,
            'sort_order' => $this->sort_order,
            'level' => $this->level,
            'status' => $this->status,
            'effective_date' => $this->effective_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Relationships
            'parent_division' => $this->whenLoaded('parentDivision', function () {
                return [
                    'id' => $this->parentDivision->id,
                    'division_title' => $this->parentDivision->division_title,
                    'division_number' => $this->parentDivision->division_number,
                ];
            }),
            
            'child_divisions' => $this->whenLoaded('childDivisions', function () {
                return StatuteDivisionResource::collection($this->childDivisions);
            }),
            
            'provisions' => $this->whenLoaded('provisions', function () {
                return StatuteProvisionResource::collection(
                    $this->provisions->whereNull('parent_provision_id')
                );
            }),
            
            'child_divisions_count' => $this->when($this->relationLoaded('childDivisions'), $this->childDivisions->count()),
            'provisions_count' => $this->when($this->relationLoaded('provisions'), 
                $this->provisions->whereNull('parent_provision_id')->count()),
        ];
    }
}