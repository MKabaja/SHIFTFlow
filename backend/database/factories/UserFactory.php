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

            'max_hours_per_month' => null,
            'max_hours_per_quarter' => null,
            'min_break_hours' => null,

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

            'max_hours_per_month' => 160,
            'max_hours_per_quarter' => 480,
            'min_break_hours' => 11,
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

    public function withHourLimits(
        ?int $monthly = 160,
        ?int $quarterly = 480,
        ?int $breakHours = 11
    ): static {
        return $this->state(fn (array $attributes) => [
            'max_hours_per_month' => $monthly,
            'max_hours_per_quarter' => $quarterly,
            'min_break_hours' => $breakHours,
        ]);
    }

    public function withoutHourLimits(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_hours_per_month' => null,
            'max_hours_per_quarter' => null,
            'min_break_hours' => null,
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
