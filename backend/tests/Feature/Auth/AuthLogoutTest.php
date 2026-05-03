<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->password = 'Secret123!';
});

test('logout succeeds with valid token', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $manager */
    $manager = User::factory()->manager()->create(['password' => $this->password]);

    $token = $this->postJson('/api/auth/login', [
        'login' => $manager->login,
        'password' => $this->password,
    ])->json('access_token');

    $this->withToken($token)->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJson(['message' => 'Logged out successfully']);
});

test('logout fails without token', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/auth/logout')
        ->assertUnauthorized();
});

test('blacklisted token is rejected after logout', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $manager */
    $manager = User::factory()->manager()->create(['password' => $this->password]);

    $token = $this->postJson('/api/auth/login', [
        'login' => $manager->login,
        'password' => $this->password,
    ])->json('access_token');

    $this->withToken($token)->postJson('/api/auth/logout')
        ->assertOk();

    $this->withToken($token)->getJson('/api/auth/me')
        ->assertUnauthorized();
});
