<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'login' => $this->login,
            'role' => $this->role,
            'is_active' => (bool) $this->is_active,

            'hourly_rate' => $this->hourly_rate ? (float) $this->hourly_rate : null,
            'max_hours' => $this->max_hours_per_month,
            'contract_type' => $this->contract_type,

            'positions' => PositionResource::collection($this->whenLoaded('positions')),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
