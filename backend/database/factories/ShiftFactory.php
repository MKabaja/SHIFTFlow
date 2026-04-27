<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'position_id' => Position::factory(),
            'schedule_id' => null,

            'date' => now()->format('Y-m-d'),
            'shift_start' => '08:00',
            'shift_end' => '16:00',

            'minutes_worked' => null,
            'hourly_rate' => null,
            'status' => 'scheduled',
            'notes' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forPosition(Position $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position_id' => $position->id,
        ]);
    }

    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }

    public function withTimes(string $start, string $end): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_start' => $start,
            'shift_end' => $end,
        ]);
    }

    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_start' => '06:00',
            'shift_end' => '14:00',
        ]);
    }

    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_start' => '14:00',
            'shift_end' => '22:00',
        ]);
    }

    public function night(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_start' => '22:00',
            'shift_end' => '06:00',
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }

    public function withHourlyRate(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'hourly_rate' => $rate,
        ]);
    }

    public function withMinuteWorked(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'minutes_worked' => $minutes,
        ]);
    }
}
