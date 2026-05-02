<?php

declare(strict_types=1);
use App\Models\User;

const LOGOUT_URL = '/api/auth/logout';

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->password = 'Secret123!';
});

test('logout succeeds with valid token', function () {
    /** @var \Tests\TestCase $this */
    $manager = User::factory()->manager()->create([
        'password' => $this->password,
    ]);
    $request = $this->postJson(LOGIN_URL, [
        'login' => $manager->login,
        'password' => $this->password,
    ]);
    $token = $request->json('access_token');

    $this->withToken($token)->postJson(LOGOUT_URL)
        ->assertOk()
        ->assertJson(['message' => 'Logged out successfully']);
});

test('logout fails without token', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson(LOGOUT_URL)
        ->assertUnauthorized();
});

test('blacklisted token is rejected after logout', function () {
    /** @var \Tests\TestCase $this */
    $manager = User::factory()->manager()->create([
        'password' => $this->password,
    ]);
    $request = $this->postJson(LOGIN_URL, [
        'login' => $manager->login,
        'password' => $this->password,
    ]);
    $token = $request->json('access_token');

    $this->withToken($token)->postJson(LOGOUT_URL)
        ->assertOk();

    $this->withToken($token)->getJson(AUTH_ME_URL)
        ->assertUnauthorized();
});
