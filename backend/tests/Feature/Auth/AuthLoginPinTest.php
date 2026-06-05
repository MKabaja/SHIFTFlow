<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->pin = '2222';
});

test('login pin succeeds with valid pin', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $employee */
    $employee = User::factory()->employee()->withPin($this->pin)->create();

    $response = $this->postJson('/api/auth/login-pin', [
        'login' => $employee->login,
        'pin' => $this->pin,
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure(['user'])
        ->assertJsonPath('user.role', 'employee');

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
