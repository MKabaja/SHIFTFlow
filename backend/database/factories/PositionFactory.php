<?php

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
            'name' => 'T'.str_pad(self::$counter, 2, '0', STR_PAD_LEFT),  // T01-T99
            'description' => fake()->sentence(),
            'created_by' => null,
        ];
    }
}
