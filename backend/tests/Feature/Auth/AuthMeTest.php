<?php

declare(strict_types=1);

const AUTH_ME_URL = '/api/auth/me';

test('me returns authenticated user data with positions', function () {
    /** @var \Tests\TestCase $this */
    $this->seedPositions();
    $this->actingAsEmployee();
    $this->employee->positions()->attach($this->b1->id);

    $this->getJson(AUTH_ME_URL)
        ->assertOk()
        ->assertJsonStructure(['id', 'name', 'login', 'role', 'positions', 'status', 'hourly_rate'])
        ->assertJsonPath('name', $this->employee->name)
        ->assertJsonPath('role', $this->employee->role)
        ->assertJsonIsArray('positions');
});

test('me returns empty positions array when user has none', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsEmployee();
    $this->getJson(AUTH_ME_URL)
        ->assertOk()
        ->assertJsonPath('positions', []);

});
test('me fails without token', function () {
    /** @var \Tests\TestCase $this */
    $this->getJson(AUTH_ME_URL)
        ->assertUnauthorized();
});
