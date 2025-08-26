<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatuteProvisionResource extends JsonResource
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
            'division_id' => $this->division_id,
            'parent_provision_id' => $this->parent_provision_id,
            'provision_type' => $this->provision_type,
            'provision_number' => $this->provision_number,
            'provision_title' => $this->provision_title,
            'provision_text' => $this->provision_text,
            'marginal_note' => $this->marginal_note,
            'interpretation_note' => $this->interpretation_note,
            'range' => $this->range,
            'sort_order' => $this->sort_order,
            'level' => $this->level,
            'status' => $this->status,
            'effective_date' => $this->effective_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Relationships
            'division' => $this->whenLoaded('division', function () {
                return [
                    'id' => $this->division->id,
                    'division_title' => $this->division->division_title,
                    'division_number' => $this->division->division_number,
                    'division_type' => $this->division->division_type,
                ];
            }),
            
            'parent_provision' => $this->whenLoaded('parentProvision', function () {
                return [
                    'id' => $this->parentProvision->id,
                    'provision_title' => $this->parentProvision->provision_title,
                    'provision_number' => $this->parentProvision->provision_number,
                ];
            }),
            
            'child_provisions' => $this->whenLoaded('childProvisions', function () {
                return StatuteProvisionResource::collection($this->childProvisions);
            }),
            
            'child_provisions_count' => $this->when($this->relationLoaded('childProvisions'), $this->childProvisions->count()),
            'views_count' => $this->viewsCount(),
        ];
    }
}