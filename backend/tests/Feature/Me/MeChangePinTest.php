<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->pin = '1234';
});

test('employee can change PIN with correct current PIN', function () {
    /** @var \Tests\TestCase $this */
    $this->employee = User::factory()->employee()->withPin($this->pin)->create();
    $this->actingAs($this->employee, 'api');

    $this->patchJson('/api/me/pin', [
        'current_pin' => $this->pin,
        'new_pin' => '5678',
        'new_pin_confirmation' => '5678',
    ])
        ->assertOk()
        ->assertJson(['message' => 'PIN changed successfully.']);
});

test('employee can set first PIN when no PIN is set', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsEmployee();

    $this->patchJson('/api/me/pin', [
        'new_pin' => '5678',
        'new_pin_confirmation' => '5678',
    ])
        ->assertOk()
        ->assertJson(['message' => 'PIN changed successfully.']);

    expect(Hash::check('5678', $this->employee->fresh()->pin_hashed))->toBeTrue();
});

test('change PIN fails with wrong current PIN', function () {
    /** @var \Tests\TestCase $this */
    $this->employee = User::factory()->employee()->withPin($this->pin)->create();
    $this->actingAs($this->employee, 'api');

    $this->patchJson('/api/me/pin', [
        'current_pin' => '0000',
        'new_pin' => '5678',
        'new_pin_confirmation' => '5678',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_pin']);
});

test('change PIN fails when new PINs do not match', function () {
    /** @var \Tests\TestCase $this */
    $this->employee = User::factory()->employee()->withPin($this->pin)->create();
    $this->actingAs($this->employee, 'api');

    $this->patchJson('/api/me/pin', [
        'current_pin' => $this->pin,
        'new_pin' => '5678',
        'new_pin_confirmation' => '9999',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['new_pin']);
});

test('change PIN fails when PIN is not 4 digits', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsEmployee();

    $this->patchJson('/api/me/pin', [
        'new_pin' => '12',
        'new_pin_confirmation' => '12',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['new_pin']);
});

test('admin cannot change PIN', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsAdmin();

    $this->patchJson('/api/me/pin', [
        'new_pin' => '5678',
        'new_pin_confirmation' => '5678',
    ])
        ->assertForbidden();
});

test('manager cannot change PIN', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsManager();

    $this->patchJson('/api/me/pin', [
        'new_pin' => '5678',
        'new_pin_confirmation' => '5678',
    ])
        ->assertForbidden();
});

test('change PIN requires authentication', function () {
    /** @var \Tests\TestCase $this */
    $this->patchJson('/api/me/pin', [
        'new_pin' => '5678',
        'new_pin_confirmation' => '5678',
    ])
        ->assertUnauthorized();
});
