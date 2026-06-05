<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NewsPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsPostFactory extends Factory
{
    protected $model = NewsPost::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(rand(4, 8), false),
            'content' => fake()->paragraphs(rand(2, 4), true),
            'is_important' => false,
            'author_id' => User::factory()->admin(),
        ];
    }

    public function important(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_important' => true,
        ]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'author_id' => $user->id,
        ]);
    }
}
