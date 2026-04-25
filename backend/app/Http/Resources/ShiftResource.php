<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
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
            'schedule_id' => $this->schedule_id,

            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'position_id' => $this->position_id,
            'position_name' => $this->whenLoaded('position', fn () => $this->position->name),

            'date' => $this->date->format('Y-m-d'),
            'shift_start' => $this->shift_start->format('H:i'),
            'shift_end' => $this->shift_end->format('H:i'),

            'minutes_worked' => $this->minutes_worked,
            'hours_worked' => $this->minutes_worked !== null ? round($this->minutes_worked / 60, 2) : null,

            'status' => $this->status,
            'notes' => $this->notes,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
