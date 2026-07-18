<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /**
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

            'hourly_rate' => $this->hourly_rate !== null ? (float) $this->hourly_rate : null,
            'monthly_hour_limit' => $this->minutesToHours($this->max_minutes_per_month),
            'quarter_hour_limit' => $this->minutesToHours($this->max_minutes_per_quarter),
            'break_limit' => $this->minutesToHours($this->min_break_minutes),

            'contract_type' => $this->contract_type,
            'locale' => $this->locale,

            'positions' => PositionResource::collection($this->whenLoaded('positions')),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function minutesToHours(?int $minutes): ?float
    {
        return $minutes !== null ? round($minutes / 60, 1) : null;
    }
}
