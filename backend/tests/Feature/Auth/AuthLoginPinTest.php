<?php

declare(strict_types=1);

use App\Models\User;

const LOGIN_PIN_URL = '/api/auth/login-pin';

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->pin = '2222';
});

test('login pin succeeds with valid pin', function () {
    /** @var \Tests\TestCase $this */
    $employee = User::factory()->employee()->withPin($this->pin)->create();

    $this->postJson(LOGIN_PIN_URL, [
        'login' => $employee->login,
        'pin' => $this->pin,
    ])
        ->assertOk()
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'user' => ['id', 'name', 'login', 'role'],
        ])
        ->assertJsonPath('token_type', 'bearer')
        ->assertJsonPath('user.role', 'employee');
});

test('login pin fails with wrong pin', function () {
    /** @var \Tests\TestCase $this */
    $employee = User::factory()->employee()->withPin($this->pin)->create();

    $this->postJson(LOGIN_PIN_URL, [
        'login' => $employee->login,
        'pin' => '9999',
    ])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid pin or login']);
});

test('login pin fails when user is inactive', function () {
    /** @var \Tests\TestCase $this */
    $employee = User::factory()->employee()->inactive()->withPin($this->pin)->create();

    $this->postJson(LOGIN_PIN_URL, [
        'login' => $employee->login,
        'pin' => $this->pin,
    ])
        ->assertForbidden()
        ->assertJson(['message' => 'Account deactivated.']);
});

test('login pin fails when user has no pin set', function () {
    /** @var \Tests\TestCase $this */
    $employee = User::factory()->employee()->create();

    $this->postJson(LOGIN_PIN_URL, [
        'login' => $employee->login,
        'pin' => $this->pin,
    ])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid pin or login']);
});
