<?php

declare(strict_types=1);

use App\Models\Availability;
use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsEmployee();
    $this->seedPositions();
});

test('employee can declare their own availability', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/availabilities', [
        'date' => '2026-01-15',
        'is_available' => true,
        'notes' => 'Chętnie przyjdę',
    ])
        ->assertStatus(201)
        ->assertJsonFragment(['is_available' => true]);

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $this->employee->id,
        'date' => '2026-01-15',
        'is_available' => true,
    ]);
});

test('employee cannot declare availability for another user', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $otherUser */
    $otherUser = User::factory()->employee()->create();

    $this->postJson('/api/availabilities', [
        'user_id' => $otherUser->id,
        'date' => '2026-01-16',
        'is_available' => true,
    ])
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
    /** @var User $employee */
    $employee = User::factory()->employee()->create();

    $this->actingAsAdmin()
        ->postJson('/api/availabilities', [
            'user_id' => $employee->id,
            'date' => '2026-01-20',
            'is_available' => false,
            'notes' => 'Urlop',
        ])
        ->assertStatus(201);

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $employee->id,
        'is_available' => false,
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

    $this->postJson('/api/availabilities', [
        'date' => '2026-01-15',
        'is_available' => false,
    ])
        ->assertStatus(200);

    expect(Availability::where('user_id', $this->employee->id)->count())
        ->toBe(1);

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $this->employee->id,
        'date' => '2026-01-15',
        'is_available' => false,
    ]);
});

test('employee cannot view other users availability', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $otherUser */
    $otherUser = User::factory()->employee()->create();

    $this->getJson("/api/availabilities?user_id={$otherUser->id}")
        ->assertForbidden();
});

test('admin can list availabilities of all users', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $other */
    $other = User::factory()->employee()->create();

    Availability::factory()->forUser($this->employee->id)->forDate('2026-02-10')->create();
    Availability::factory()->forUser($other->id)->forDate('2026-02-11')->unavailable()->create();

    $this->actingAsAdmin()
        ->getJson('/api/availabilities')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

test('manager can list availabilities of all users', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $other */
    $other = User::factory()->employee()->create();

    Availability::factory()->forUser($this->employee->id)->forDate('2026-02-10')->create();
    Availability::factory()->forUser($other->id)->forDate('2026-02-11')->unavailable()->create();

    $this->actingAsManager()
        ->getJson('/api/availabilities')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

test('manager cannot create availability', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $employee */
    $employee = User::factory()->employee()->create();

    $this->actingAsManager()
        ->postJson('/api/availabilities', [
            'user_id' => $employee->id,
            'date' => '2026-03-01',
            'is_available' => true,
        ])
        ->assertForbidden();
});
