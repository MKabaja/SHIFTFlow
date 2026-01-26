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
            'email' => $this->email ?? null,
            'login' => $this->login,
            'role' => $this->role,
            'is_active' => (bool) $this->is_active,

            'hourly_rate' => $this->hourly_rate ? (float) $this->hourly_rate : null,
            'monthly_hour_limit' => round($this->max_minutes_per_month / 60, 1),
            'quarter_hour_limit' => round($this->max_minutes_per_quarter / 60, 1),
            'break_limit' => round($this->min_break_minutes / 60, 1),

            'contract_type' => $this->contract_type,

            'positions' => PositionResource::collection($this->whenLoaded('positions')),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
