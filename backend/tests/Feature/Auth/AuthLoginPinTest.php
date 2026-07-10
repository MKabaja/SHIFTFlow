<?php

declare(strict_types=1);

use App\Models\Position;
use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->pin = '2222';
});

test('login pin succeeds with valid pin and returns full user resource', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $employee */
    $employee = User::factory()->employee()->withPin($this->pin)->create();
    $position = Position::factory()->create();
    $employee->positions()->attach($position->id);

    $response = $this->postJson('/api/auth/login-pin', [
        'login' => $employee->login,
        'pin' => $this->pin,
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'login', 'role', 'locale', 'positions' => [['id', 'name', 'description', 'color']]]])
        ->assertJsonPath('data.role', 'employee')
        ->assertJsonPath('data.positions.0.id', $position->id);

    expect($response->getCookie('jwt_token', false))->not->toBeNull();
});

test('login pin fails with wrong pin', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $employee */
    $employee = User::factory()->employee()->withPin($this->pin)->create();

    $this->postJson('/api/auth/login-pin', [
        'login' => $employee->login,
        'pin' => '9999',
    ])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid pin or login']);
});

test('login pin fails when user is inactive', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $employee */
    $employee = User::factory()->employee()->inactive()->withPin($this->pin)->create();

    $this->postJson('/api/auth/login-pin', [
        'login' => $employee->login,
        'pin' => $this->pin,
    ])
        ->assertForbidden()
        ->assertJson(['message' => 'Account deactivated.']);
});

test('login pin fails when user has no pin set', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $employee */
    $employee = User::factory()->employee()->create();

    $this->postJson('/api/auth/login-pin', [
        'login' => $employee->login,
        'pin' => $this->pin,
    ])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid pin or login']);
});
