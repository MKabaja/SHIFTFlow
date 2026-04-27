<?php

declare(strict_types=1);

use App\Models\Availability;
use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $this->admin
     */
    $this->actingAsEmployee();
    $this->seedPositions();

});

test('employee can declare their own availability', function () {
    /** @var \Tests\TestCase $this */
    $payload = [
        'date' => '2026-01-15',
        'is_available' => true,
        'notes' => 'Chętnie przyjdę',
    ];

    $this->postJson('/api/availabilities', $payload)
        ->assertStatus(201)
        ->assertJsonFragment(['is_available' => true]);

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $this->employee->id,
        'date' => '2026-01-15',
        'is_available' => 1,
    ]);
});

test('employee cannot declare availability for another user', function () {
    /** @var \Tests\TestCase $this */
    $otherUser = User::factory()->employee()->create();

    $payload = [
        'user_id' => $otherUser->id,
        'date' => '2026-01-16',
        'is_available' => true,
    ];

    $this->postJson('/api/availabilities', $payload)
        ->assertStatus(201);

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $this->employee->id,
        'date' => '2026-01-16',
    ]);

    $this->assertDatabaseMissing('availabilities', [
        'user_id' => $otherUser->id,
    ]);
});

test('admin can declare availability for an employee', function () {
    /** @var \Tests\TestCase $this */
    $employee = User::factory()
        ->employee()
        ->create();

    $payload = [
        'user_id' => $employee->id,
        'date' => '2026-01-20',
        'is_available' => false,
        'notes' => 'Urlop',
    ];

    $this->actingAsAdmin()
        ->postJson('/api/availabilities', $payload)
        ->assertStatus(201);

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $employee->id,
        'is_available' => 0,
        'notes' => 'Urlop',
    ]);
});

test('availability is updated if exists for the same date', function () {
    /** @var \Tests\TestCase $this */
    Availability::create([
        'user_id' => $this->employee->id,
        'date' => '2026-01-15',
        'is_available' => true,
    ]);

    $payload = [
        'date' => '2026-01-15',
        'is_available' => false,
    ];

    $this->postJson('/api/availabilities', $payload)
        ->assertStatus(200);

    expect(Availability::where('user_id', $this->employee->id)
        ->count())
        ->toBe(1);

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $this->employee->id,
        'date' => '2026-01-15',
        'is_available' => 0,
    ]);
});

test('employee cannot view other users availability', function () {
    /** @var \Tests\TestCase $this */
    $otherUser = User::factory()
        ->employee()
        ->create();

    $this->getJson("/api/availabilities?user_id={$otherUser->id}")
        ->assertStatus(403); // Forbidden
});
