<?php

declare(strict_types=1);

use App\Models\Schedule;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsManager();
});

test('manager can list and filter schedules', function () {
    /** @var \Tests\TestCase $this */
    /** @var Schedule $targetSchedule */
    $targetSchedule = Schedule::factory()->month(1)->draft()->create();

    Schedule::factory()->month(2)->draft()->create();

    $this->getJson('/api/schedules?month=1&year=2026')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'month',
                    'year',
                    'status',
                    'description',
                    'published_at',
                    'created_by',
                    'total_shifts',
                    'created_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'total'],
            'links' => ['first', 'last', 'prev', 'next'],
        ])
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $targetSchedule->id,
            'month' => 1,
            'year' => 2026,
        ]);
});

test('manager can create a schedule', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/schedules', [
        'name' => 'Grafik Marzec 2026',
        'month' => 3,
        'year' => 2026,
        'description' => 'Wersja robocza',
    ])
        ->assertCreated()
        ->assertJsonFragment([
            'name' => 'Grafik Marzec 2026',
            'status' => 'draft',
        ]);

    $this->assertDatabaseHas('schedules', [
        'month' => 3,
        'year' => 2026,
        'created_by' => $this->manager->id,
        'status' => 'draft',
    ]);
});

test('manager can update schedule name but month change is ignored', function () {
    /** @var \Tests\TestCase $this */
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['month' => 5, 'year' => 2026, 'name' => 'Stara Nazwa']);

    $this->putJson("/api/schedules/{$schedule->id}", [
        'name' => 'Nowa Nazwa',
        'description' => 'Zaktualizowany opis',
        'month' => 12,
    ])
        ->assertOk()
        ->assertJsonFragment(['name' => 'Nowa Nazwa']);

    $this->assertDatabaseHas('schedules', [
        'id' => $schedule->id,
        'name' => 'Nowa Nazwa',
        'month' => 5,
    ]);
});

test('manager can delete a schedule', function () {
    /** @var \Tests\TestCase $this */
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create();

    $this->deleteJson("/api/schedules/{$schedule->id}")
        ->assertOk();

    $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
});
