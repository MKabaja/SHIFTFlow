<?php

declare(strict_types=1);

test('admin can change locale to en', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsAdmin();

    $this->patchJson('/api/me/locale', ['locale' => 'en'])
        ->assertOk()
        ->assertJson(['message' => 'Locale changed successfully.']);

    expect($this->admin->fresh()->locale)->toBe('en');
});

test('manager can change locale', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsManager();

    $this->patchJson('/api/me/locale', ['locale' => 'en'])
        ->assertOk();
});

test('employee can change locale', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsEmployee();

    $this->patchJson('/api/me/locale', ['locale' => 'en'])
        ->assertOk();
});

test('locale defaults to pl for new user', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsAdmin();

    expect($this->admin->locale)->toBe('pl');
});

test('change locale fails with unsupported locale', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsAdmin();

    $this->patchJson('/api/me/locale', ['locale' => 'de'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['locale']);
});

test('change locale fails when locale is missing', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsAdmin();

    $this->patchJson('/api/me/locale', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['locale']);
});

test('change locale requires authentication', function () {
    /** @var \Tests\TestCase $this */
    $this->patchJson('/api/me/locale', ['locale' => 'en'])
        ->assertUnauthorized();
});
