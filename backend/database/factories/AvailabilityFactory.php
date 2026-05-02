<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('2026-01-01', '2026-12-31')->format('Y-m-d'),
            'is_available' => true,
            'notes' => null,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
