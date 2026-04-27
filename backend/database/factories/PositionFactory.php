<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'name' => 'T'.str_pad((string) self::$counter, 2, '0', STR_PAD_LEFT),  // T01-T99
            'description' => fake()->sentence(),
            'created_by' => null,
        ];
    }

    public function B1(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'B1',
            'description' => 'Ticketing Officer 1',

        ]);
    }

    public function B2(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'B2',
            'description' => 'Ticketing Officer 2',

        ]);
    }

    public function B3(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'B3',
            'description' => 'Ticketing Officer 3',

        ]);
    }

    public function K1(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'K1',
            'description' => 'Tour Guide Coordinator 1',

        ]);
    }

    public function K2(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'K2',
            'description' => 'Tour Guide Coordinator 2',

        ]);
    }

    public function PD(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'PD',
            'description' => 'Dispatcher Assistant',

        ]);
    }

    public function PTG(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'PTG',
            'description' => 'Tour Guide Assistant',

        ]);
    }
}
