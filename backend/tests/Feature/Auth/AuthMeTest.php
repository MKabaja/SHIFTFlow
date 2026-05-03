<?php

declare(strict_types=1);

test('me returns authenticated user data with positions', function () {
    /** @var \Tests\TestCase $this */
    $this->seedPositions();
    $this->actingAsEmployee();
    $this->employee->positions()->attach($this->b1->id);

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'login', 'role', 'positions', 'is_active', 'hourly_rate']])
        ->assertJsonPath('data.name', $this->employee->name)
        ->assertJsonPath('data.role', $this->employee->role)
        ->assertJsonIsArray('data.positions');
});

test('me returns empty positions array when user has none', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsEmployee();

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.positions', []);
});

test('me fails without token', function () {
    /** @var \Tests\TestCase $this */
    $this->getJson('/api/auth/me')
        ->assertUnauthorized();
});
