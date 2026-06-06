<?php

declare(strict_types=1);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->password = 'Secret123!';
});

test('admin can change password with correct current password', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsAdmin();
    $this->admin->update(['password' => $this->password]);

    $this->patchJson('/api/me/password', [
        'current_password' => $this->password,
        'new_password' => 'NewSecret456!',
        'new_password_confirmation' => 'NewSecret456!',
    ])
        ->assertOk()
        ->assertJson(['message' => 'Password changed successfully.']);
});

test('manager can change password with correct current password', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsManager();
    $this->manager->update(['password' => $this->password]);

    $this->patchJson('/api/me/password', [
        'current_password' => $this->password,
        'new_password' => 'NewSecret456!',
        'new_password_confirmation' => 'NewSecret456!',
    ])
        ->assertOk()
        ->assertJson(['message' => 'Password changed successfully.']);
});

test('change password fails with wrong current password', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsAdmin();

    $this->patchJson('/api/me/password', [
        'current_password' => 'wrong-password',
        'new_password' => 'NewSecret456!',
        'new_password_confirmation' => 'NewSecret456!',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});

test('change password fails when new passwords do not match', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsAdmin();
    $this->admin->update(['password' => $this->password]);

    $this->patchJson('/api/me/password', [
        'current_password' => $this->password,
        'new_password' => 'NewSecret456!',
        'new_password_confirmation' => 'DifferentSecret!',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['new_password']);
});

test('employee cannot change password', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsEmployee();

    $this->patchJson('/api/me/password', [
        'current_password' => $this->password,
        'new_password' => 'NewSecret456!',
        'new_password_confirmation' => 'NewSecret456!',
    ])
        ->assertForbidden();
});

test('change password requires authentication', function () {
    /** @var \Tests\TestCase $this */
    $this->patchJson('/api/me/password', [
        'current_password' => $this->password,
        'new_password' => 'NewSecret456!',
        'new_password_confirmation' => 'NewSecret456!',
    ])
        ->assertUnauthorized();
});
