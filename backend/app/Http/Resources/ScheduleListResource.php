<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'month' => $this->month,
            'year' => $this->year,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),

            'created_by' => $this->creator?->name,

            'total_shifts' => $this->shifts_count ?? $this->shifts?->count(),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
