<?php

use App\Models\Position;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;

test('admin cannot delete position with shifts', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $WR2 = Position::create([
        'name' => 'WR2',
        'description' => 'Winda regis 2',
    ]);

    $employee = User::factory()->create([
        'role' => 'employee',
        'max_hours_per_month' => 180,
    ]);
    $employee->positions()->attach($WR2->id);

    // Musimy utworzyć Schedule, aby podpiąć pod niego Shift
    $schedule = Schedule::factory()->create();

    // Tworzymy Shift (nie Schedule!), bo to on trzyma position_id
    Shift::create([
        'schedule_id' => $schedule->id,
        'user_id' => $employee->id,
        'position_id' => $WR2->id,
        'date' => '2025-10-08',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
    ]);

    $response = $this->actingAs($admin)
        ->deleteJson("/api/positions/{$WR2->id}");

    $response->assertStatus(409); // Conflict
    $this->assertDatabaseHas('positions', ['id' => $WR2->id]);
});
