<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Position;
use App\Models\User;
use App\Models\Schedule;

class PositionTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_cannot_delete_position_with_schedules()
    {

        $admin = User::factory()->create(['role' => 'admin']);
        $WR2 = Position::create([
            'name' => 'WR2',
            'description' => 'Winda regis 2'
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'max_hours_per_month' => 180
        ]);
        $employee->positions()->attach($WR2->id);


        $schedule = Schedule::create([
            'user_id' => $employee->id,
            'position_id' => $WR2->id,
            'date' => '2025-10-08',
            'shift_start' => '08:00',
            'shift_end' => '16:00',
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/positions/{$WR2->id}");

        $response->assertStatus(409);

        $this->assertDatabaseHas('positions', ['id' => $WR2->id]);
    }
}
