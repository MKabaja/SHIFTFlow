<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isSystem = is_null($this->created_by);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'creator_name' => $this->when(! $isSystem, $this->creator?->name),
            'created_at' => $this->when(! $isSystem, $this->created_at?->toIso8601String()),

        ];
    }
}
