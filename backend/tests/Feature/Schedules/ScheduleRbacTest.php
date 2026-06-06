<?php

declare(strict_types=1);

use App\Models\Schedule;
use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->manager = User::factory()->manager()->create();
    $this->employee = User::factory()->employee()->create();

    $this->publishedSchedule = Schedule::factory()->published()->create([
        'created_by' => $this->manager->id,
        'month' => 1,
        'year' => 2026,
    ]);

    $this->draftSchedule = Schedule::factory()->draft()->create([
        'created_by' => $this->manager->id,
        'month' => 2,
        'year' => 2026,
    ]);
});

test('employee can list schedules and sees only published', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs($this->employee)
        ->getJson('/api/schedules')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $this->publishedSchedule->id])
        ->assertJsonMissing(['id' => $this->draftSchedule->id]);
});

test('employee can view published schedule', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs($this->employee)
        ->getJson("/api/schedules/{$this->publishedSchedule->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $this->publishedSchedule->id]);
});

test('employee cannot view draft schedule', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs($this->employee)
        ->getJson("/api/schedules/{$this->draftSchedule->id}")
        ->assertForbidden();
});

test('manager can list all schedules', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs($this->manager)
        ->getJson('/api/schedules')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('manager can view draft schedule', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs($this->manager)
        ->getJson("/api/schedules/{$this->draftSchedule->id}")
        ->assertOk();
});

test('employee cannot create schedule', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs($this->employee)
        ->postJson('/api/schedules', [
            'name' => 'Test',
            'month' => 3,
            'year' => 2026,
        ])
        ->assertForbidden();
});

test('employee cannot delete schedule', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs($this->employee)
        ->deleteJson("/api/schedules/{$this->publishedSchedule->id}")
        ->assertForbidden();
});
