<?php

declare(strict_types=1);

use App\Models\Position;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->manager = User::factory()->manager()->create();
    $this->employee = User::factory()->employee()->create();
    $this->position = Position::factory()->create();

    $this->employee->positions()->attach($this->position->id);

    $this->schedule = Schedule::factory()->draft()->create([
        'created_by' => $this->manager->id,
        'month' => 1,
        'year' => 2026,
    ]);
});

test('employee cannot crud schedules', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs($this->employee)
        ->deleteJson("/api/schedules/{$this->schedule->id}")
        ->assertForbidden();

    $this->actingAs($this->employee)
        ->postJson("/api/schedules/{$this->schedule->id}/publish")
        ->assertForbidden();
});

test('schedule cascade deletes shifts', function () {
    /** @var \Tests\TestCase $this */
    /** @var Shift $shift */
    $shift = Shift::factory()->create([
        'schedule_id' => $this->schedule->id,
        'user_id' => $this->employee->id,
    ]);

    $this->actingAs($this->manager)
        ->deleteJson("/api/schedules/{$this->schedule->id}")
        ->assertOk();

    $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
});

test('add batch shifts success', function () {
    /** @var \Tests\TestCase $this */
    $payload = [
        'shifts' => [
            [
                'client_temp_id' => 'row_1',
                'user_id' => $this->employee->id,
                'position_id' => $this->position->id,
                'date' => '2026-01-02',
                'shift_start' => '08:00',
                'shift_end' => '16:00',
                'status' => 'scheduled',
            ],
            [
                'client_temp_id' => 'row_2',
                'user_id' => $this->employee->id,
                'position_id' => $this->position->id,
                'date' => '2026-01-03',
                'shift_start' => '10:00',
                'shift_end' => '18:00',
                'status' => 'scheduled',
            ],
        ],
    ];

    $this->actingAs($this->manager)
        ->postJson("/api/schedules/{$this->schedule->id}/shifts/batch", $payload)
        ->assertCreated()
        ->assertJsonFragment(['message' => 'Batch created successfully'])
        ->assertJsonCount(2, 'shifts');

    $this->assertDatabaseHas('shifts', [
        'schedule_id' => $this->schedule->id,
        'date' => '2026-01-02',
        'minutes_worked' => 480,
    ]);
});

test('add batch returns mapped errors', function () {
    /** @var \Tests\TestCase $this */
    $payload = [
        'shifts' => [
            [
                'client_temp_id' => 'row_ok',
                'user_id' => $this->employee->id,
                'position_id' => $this->position->id,
                'date' => '2026-01-02',
                'shift_start' => '08:00',
                'shift_end' => '16:00',
            ],
            [
                'client_temp_id' => 'row_bad',
                'user_id' => 999999,
                'position_id' => $this->position->id,
                'date' => '2026-01-02',
                'shift_start' => '08:00',
                'shift_end' => '16:00',
            ],
        ],
    ];

    $this->actingAs($this->manager)
        ->postJson("/api/schedules/{$this->schedule->id}/shifts/batch", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shifts.1.user_id']);
});

test('batch internal time conflict', function () {
    /** @var \Tests\TestCase $this */
    $payload = [
        'shifts' => [
            [
                'client_temp_id' => 'row_1',
                'user_id' => $this->employee->id,
                'position_id' => $this->position->id,
                'date' => '2026-01-05',
                'shift_start' => '08:00',
                'shift_end' => '16:00',
            ],
            [
                'client_temp_id' => 'row_2',
                'user_id' => $this->employee->id,
                'position_id' => $this->position->id,
                'date' => '2026-01-05',
                'shift_start' => '12:00',
                'shift_end' => '20:00',
            ],
        ],
    ];

    $response = $this->actingAs($this->manager)
        ->postJson("/api/schedules/{$this->schedule->id}/shifts/batch", $payload)
        ->assertUnprocessable();

    $errors = $response->json('errors');
    expect($errors)->toHaveKey('row_2');
    expect($errors['row_2'])->toHaveKey('conflict');
});

test('publish changes status', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs($this->manager)
        ->postJson("/api/schedules/{$this->schedule->id}/publish")
        ->assertOk();

    $this->schedule->refresh();
    expect($this->schedule->status)->toBe('published');
    expect($this->schedule->published_at)->not->toBeNull();
});

test('employee sees only published shifts', function () {
    /** @var \Tests\TestCase $this */
    Shift::factory()->create([
        'schedule_id' => $this->schedule->id,
        'user_id' => $this->employee->id,
    ]);

    $this->actingAs($this->employee)
        ->getJson('/api/shifts')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('batch fails when shifts array is empty', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs($this->manager)
        ->postJson("/api/schedules/{$this->schedule->id}/shifts/batch", ['shifts' => []])
        ->assertUnprocessable();

    $this->assertDatabaseCount('shifts', 0);
});

test('batch fails when shift date does not match schedule period', function () {
    /** @var \Tests\TestCase $this */
    $payload = [
        'shifts' => [
            [
                'client_temp_id' => 'row_1',
                'user_id' => $this->employee->id,
                'position_id' => $this->position->id,
                'date' => '2025-12-01',
                'shift_start' => '08:00',
                'shift_end' => '16:00',
            ],
        ],
    ];

    $response = $this->actingAs($this->manager)
        ->postJson("/api/schedules/{$this->schedule->id}/shifts/batch", $payload)
        ->assertUnprocessable();

    expect($response->json('errors'))->toHaveKey('0.date');
    $this->assertDatabaseCount('shifts', 0);
});

test('batch fails with duplicate client_temp_id', function () {
    /** @var \Tests\TestCase $this */
    $payload = [
        'shifts' => [
            [
                'client_temp_id' => 'duplicate',
                'user_id' => $this->employee->id,
                'position_id' => $this->position->id,
                'date' => '2026-01-10',
                'shift_start' => '08:00',
                'shift_end' => '16:00',
            ],
            [
                'client_temp_id' => 'duplicate',
                'user_id' => $this->employee->id,
                'position_id' => $this->position->id,
                'date' => '2026-01-11',
                'shift_start' => '08:00',
                'shift_end' => '16:00',
            ],
        ],
    ];

    $this->actingAs($this->manager)
        ->postJson("/api/schedules/{$this->schedule->id}/shifts/batch", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shifts.0.client_temp_id']);

    $this->assertDatabaseCount('shifts', 0);
});
