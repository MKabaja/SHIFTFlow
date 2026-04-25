<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $date = fake()->dateTimeThisYear();
        $carbonDate = Carbon::instance($date);

        return [
            'name' => 'Schedule for'.' '.$carbonDate->monthName,
            'description' => fake()->sentence(),
            'month' => $carbonDate->month,
            'year' => (int) $carbonDate->year,
            'status' => 'draft',
            'published_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function month(int $monthNumber): static
    {
        $date = now()
            ->setYear(2026)
            ->setMonth($monthNumber)
            ->startOfMonth();

        return $this->state(fn (array $attributes) => [
            'name' => 'Schedule for'.' '.$date->monthName,
            'month' => $date->month,
            'year' => $date->year,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }
}
