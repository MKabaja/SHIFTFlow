<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'login' => fake()->unique()->userName(),
            'password' => 'password',
            'pin_hashed' => null,

            'role' => 'employee',
            'is_active' => true,
            'hourly_rate' => null,
            'contract_type' => 'employment_contract',

            'max_minutes_per_month' => null,
            'max_minutes_per_quarter' => null,
            'min_break_minutes' => null,

        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',

        ]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'manager',

        ]);
    }

    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'employee',

            'max_minutes_per_month' => 9600,
            'min_break_minutes' => 660,
            'max_minutes_per_quarter' => 28800,
            'hourly_rate' => 25.00,
        ]);
    }

    public function employmentContract(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_type' => 'employment_contract',
        ]);
    }

    public function mandateContract(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_type' => 'mandate_contract',
        ]);
    }

    public function withMinuteLimits(
        ?int $monthly = 9600,
        ?int $quarterly = 28800,
        ?int $breakHours = 660
    ): static {
        return $this->state(fn (array $attributes) => [
            'max_minutes_per_month' => $monthly,
            'max_minutes_per_quarter' => $quarterly,
            'min_break_minutes' => $breakHours,
        ]);
    }

    public function withoutMinuteLimits(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_minutes_per_month' => null,
            'max_minutes_per_quarter' => null,
            'min_break_minutes' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withPin(string $pin = '1234'): static
    {
        return $this->state(fn (array $attributes) => [
            'pin_hashed' => $pin,
        ]);
    }

    public function withHourlyRate(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'hourly_rate' => $rate,
        ]);
    }
}
