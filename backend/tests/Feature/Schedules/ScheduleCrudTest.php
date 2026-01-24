<?php

use App\Models\Schedule;
use App\Models\User;

it('allows manager to list and filter schedules', function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $manager
     */
    // 1. Arrange:
    $manager = User::factory()
        ->manager()
        ->create();

    $targetSchedule = Schedule::factory()
        ->month(1)
        ->draft()
        ->create();

    Schedule::factory()
        ->month(2)
        ->draft()
        ->create();
    // 2. Act:
    $response = $this->actingAs($manager)
        ->getJson('/api/schedules?month=1&year=2026');
    // 3. Assert:
    $response->assertStatus(200);

    $response->assertJsonStructure([
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
        'meta' => [
            'current_page',
            'last_page',
            'total',
        ],
        'links' => [
            'first',
            'last',
            'prev',
            'next',
        ],

    ]);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment([
        'id' => $targetSchedule->id,
        'month' => 1,
        'year' => 2026,
    ]);

});

it('allows manager to create a schedule', function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $manager
     */

    // 1. Arrange
    $manager = User::factory()
        ->manager()
        ->create();

    $payload = [
        'name' => 'Grafik Marzec 2026',
        'month' => 3,
        'year' => 2026,
        'description' => 'Wersja robocza',
    ];

    // 2. Act
    $response = $this->actingAs($manager)
        ->postJson('/api/schedules', $payload);

    // 3. Assert
    $response->assertStatus(201)
        ->assertJsonFragment([
            'name' => 'Grafik Marzec 2026',
            'status' => 'draft',
        ]);

    $this->assertDatabaseHas('schedules', [
        'month' => 3,
        'year' => 2026,
        'created_by' => $manager->id,
        'status' => 'draft',
    ]);
});

it('allows manager to update name but ignores month change', function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $manager
     */
    // 1. Arrange
    $manager = User::factory()
        ->manager()
        ->create();
    $schedule = Schedule::factory()->create([
        'month' => 5,
        'year' => 2026,
        'name' => 'Stara Nazwa',
    ]);

    $updatePayload = [
        'name' => 'Nowa Nazwa',
        'description' => 'Zaktualizowany opis',
        'month' => 12,
    ];

    // 2. Act
    $response = $this->actingAs($manager)
        ->putJson("/api/schedules/{$schedule->id}", $updatePayload);

    // 3. Assert
    $response->assertStatus(200)
        ->assertJsonFragment(['name' => 'Nowa Nazwa']);

    $this->assertDatabaseHas('schedules', [
        'id' => $schedule->id,
        'name' => 'Nowa Nazwa',
        'month' => 5,
    ]);
});
it('allows manager to delete a schedule', function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $manager
     */
    // 1. Arrange
    $manager = User::factory()
        ->manager()
        ->create();

    $schedule = Schedule::factory()
        ->create();

    // 2. Act
    $response = $this->actingAs($manager)
        ->deleteJson("/api/schedules/{$schedule->id}");

    // 3. Assert
    $response->assertStatus(200);

    $this->assertDatabaseMissing('schedules', [
        'id' => $schedule->id,
    ]);
});
