<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Position;
use App\Models\User;
use App\Models\Schedule;

class EmployeeTest extends TestCase
{

    use RefreshDatabase;

    public function test_admin_can_create_employee()
    {

        $admin = User::factory()->create(['role' => 'admin']);

        $B1 =  Position::create([
            'name' => 'B1',
            'description' => 'Bileter 1'
        ]);

        $WR2 = Position::create([
            'name' => 'WR2',
            'description' => 'Winda regis 2'
        ]);

        $payload = [

            'name' => 'Jan Kowalski',
            'pin' => '1234',
            'positions' => [$B1->id, $WR2->id],
            'hourly_rate' => 15.20
        ];

        $response = $this->actingAs($admin)->postJson('/api/employees', $payload);

        $response->assertStatus(201);


        $this->assertDatabaseHas('users', [
            'login' => 'jkowal',
            'role' => 'employee'
        ]);
        $user = User::where('login', 'jkowal')->firstOrFail();

        $this->assertCount(2, $user->positions);

        $this->assertDatabaseHas('position_user', [
            'user_id' => $user->id,
            'position_id' => $B1->id

        ]);
    }
}
