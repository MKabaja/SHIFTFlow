<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->password = 'Secret123!';
});

test('login sets httpOnly cookie', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $manager */
    $manager = User::factory()->manager()->create(['password' => $this->password]);

    $response = $this->postJson('/api/auth/login', [
        'login' => $manager->login,
        'password' => $this->password,
    ]);

    $cookie = $response->getCookie('jwt_token', false);

    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getValue())->not->toBeEmpty();
});

test('me works with cookie auth', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $manager */
    $manager = User::factory()->manager()->create(['password' => $this->password]);

    $cookieValue = $this->postJson('/api/auth/login', [
        'login' => $manager->login,
        'password' => $this->password,
    ])->getCookie('jwt_token', false)->getValue();

    $this->withCredentials()
        ->withUnencryptedCookies(['jwt_token' => $cookieValue])
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.login', $manager->login);
});

test('logout clears cookie', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $manager */
    $manager = User::factory()->manager()->create(['password' => $this->password]);

    $cookieValue = $this->postJson('/api/auth/login', [
        'login' => $manager->login,
        'password' => $this->password,
    ])->getCookie('jwt_token', false)->getValue();

    $logoutResponse = $this->withCredentials()
        ->withUnencryptedCookies(['jwt_token' => $cookieValue])
        ->postJson('/api/auth/logout')
        ->assertOk();

    $expiredCookie = $logoutResponse->getCookie('jwt_token', false);

    expect($expiredCookie)->not->toBeNull()
        ->and($expiredCookie->getExpiresTime())->toBeLessThan(time());
});
