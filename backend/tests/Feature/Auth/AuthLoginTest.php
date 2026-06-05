<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->password = 'Secret123!';
});

test('login succeeds with valid credentials', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $manager */
    $manager = User::factory()->manager()->create(['password' => $this->password]);

    $response = $this->postJson('/api/auth/login', [
        'login' => $manager->login,
        'password' => $this->password,
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure(['user'])
        ->assertJsonPath('user.login', $manager->login);

    expect($response->getCookie('jwt_token', false))->not->toBeNull();
});

test('login fails with wrong password', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $manager */
    $manager = User::factory()->manager()->create();

    $this->postJson('/api/auth/login', [
        'login' => $manager->login,
        'password' => 'wrongpassword',
    ])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid password or login!']);
});

test('login fails with wrong login', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/auth/login', [
        'login' => 'wronglogin',
        'password' => 'whatEver',
    ])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid password or login!']);
});

test('login fails when user is inactive', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $manager */
    $manager = User::factory()->manager()->inactive()->create(['password' => $this->password]);

    $this->postJson('/api/auth/login', [
        'login' => $manager->login,
        'password' => $this->password,
    ])
        ->assertForbidden()
        ->assertJson(['message' => 'Account deactivated.']);
});

test('login fails with missing fields', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/auth/login', ['login' => 'onlylogin'])
        ->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors']);
});
