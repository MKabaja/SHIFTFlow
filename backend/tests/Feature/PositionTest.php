<?php

use App\Models\Position;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $this->admin
     */
    $this->actingAsAdmin();
    $this->seedPositions();

});

test('admin cannot delete position with shifts', function () {
    /** @var \Tests\TestCase $this */
    $employee = User::factory()
        ->employee()
        ->create();

    $employee->positions()->attach($this->ptg->id);

    $schedule = Schedule::factory()->create();

    Shift::create([
        'schedule_id' => $schedule->id,
        'user_id' => $employee->id,
        'position_id' => $this->ptg->id,
        'date' => '2025-10-08',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
    ]);

    $response = $this->deleteJson("/api/positions/{$this->ptg->id}");

    $response->assertStatus(409);
    $this->assertDatabaseHas('positions', ['id' => $this->ptg->id]);
});

test('admin can list positions', function () {
    /** @var \Tests\TestCase $this */
    $this->getJson('/api/positions')
        ->assertOk()
        ->assertJsonCount(4, 'data') // from seed
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description', 'creator'],
            ],
        ]);
});

test('admin can create a new position', function () {
    /** @var \Tests\TestCase $this */
    $payload = [
        'name' => 'B9',
        'description' => 'Opis stanowiska',
    ];

    $this->postJson('/api/positions', $payload)
        ->assertCreated()
        ->assertJsonFragment(['name' => 'B9']);

    $this->assertDatabaseHas('positions', [
        'name' => 'B9',
        'created_by' => $this->admin->id,
    ]);
});

test('admin can update a position', function () {
    /** @var \Tests\TestCase $this */
    $position = Position::factory()->create([
        'name' => 'B12',
    ]);

    $payload = [
        'name' => 'B13',
        'description' => 'Nowy opis',
    ];

    $this->putJson("/api/positions/{$position->id}", $payload)
        ->assertOk();

    $this->assertDatabaseHas('positions', [
        'id' => $position->id,
        'name' => 'B13',
    ]);
});

test('admin can delete an unused position', function () {
    /** @var \Tests\TestCase $this */
    $position = Position::factory()->create();

    $this->deleteJson("/api/positions/{$position->id}")
        ->assertOk()
        ->assertJson(['message' => 'Position deleted Successfully']);

    $this->assertDatabaseMissing('positions', ['id' => $position->id]);
});
