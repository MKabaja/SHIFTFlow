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

    $cookieValue = $this->postJson('/api/auth/login', [
        'login' => $manager->login,
        'password' => $this->password,
    ])->getCookie('jwt_token', false)->getValue();

    $this->withCredentials()
        ->withUnencryptedCookies(['jwt_token' => $cookieValue])
        ->postJson('/api/auth/logout')
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

    $cookieValue = $this->postJson('/api/auth/login', [
        'login' => $manager->login,
        'password' => $this->password,
    ])->getCookie('jwt_token', false)->getValue();

    $this->withCredentials()
        ->withUnencryptedCookies(['jwt_token' => $cookieValue])
        ->postJson('/api/auth/logout')
        ->assertOk();

    $this->withCredentials()
        ->withUnencryptedCookies(['jwt_token' => $cookieValue])
        ->getJson('/api/me')
        ->assertUnauthorized();
});
