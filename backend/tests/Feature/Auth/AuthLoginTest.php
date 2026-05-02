<?php

declare(strict_types=1);

use App\Models\User;

const LOGIN_URL = '/api/auth/login';

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->password = 'Secret123!';
});

test('login succeeds with valid credentials', function () {
    /** @var \Tests\TestCase $this */
    $manager = User::factory()->manager()->create([
        'password' => $this->password,
    ]);

    $this->postJson(LOGIN_URL, [
        'login' => $manager->login,
        'password' => $this->password,
    ])
        ->assertOk()
        ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user'])
        ->assertJsonPath('token_type', 'bearer')
        ->assertJsonPath('user.login', $manager->login);
});

test('login fails with wrong password', function () {
    /** @var \Tests\TestCase $this */
    $manager = User::factory()->manager()->create();

    $this->postJson(LOGIN_URL, [
        'login' => $manager->login,
        'password' => 'wrongpassword',
    ])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid password or login!']);
});

test('login fails with wrong login', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson(LOGIN_URL, [
        'login' => 'wronglogin',
        'password' => 'whatEver',
    ])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid password or login!']);
});

test('login fails when user is inactive', function () {
    /** @var \Tests\TestCase $this */
    $manager = User::factory()->manager()->inactive()->create([
        'password' => $this->password,
    ]);

    $this->postJson(LOGIN_URL, [
        'login' => $manager->login,
        'password' => $this->password,
    ])
        ->assertForbidden()
        ->assertJson(['message' => 'Account deactivated.']);
});

test('login fails with missing fields', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson(LOGIN_URL, ['login' => 'onlylogin'])
        ->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors']);
});
