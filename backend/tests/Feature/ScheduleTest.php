<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    // TO JEST WAŻNE!
    // Ten trait mówi: "Przed każdym testem zrób 'php artisan migrate',
    // a po teście wyczyść bazę do zera".
    // Dzięki temu każdy test startuje z czystą kartą.
    use RefreshDatabase;

    public function test_manager_can_create_schedule()
    {
        // 1. ARRANGE (Przygotowanie Sceny)
        // Tworzymy Managera (żeby wysłał request)
        $manager = User::factory()->create(['role' => 'manager']);

        // Tworzymy Stanowisko (bo jest wymagane przez klucz obcy)
        $position = Position::create([
            'name' => 'B1',
            'description' => 'Bileter'
        ]);

        // Tworzymy Pracownika (któremu ustawiamy grafik)
        $employee = User::factory()->create([
            'role' => 'employee',
            'max_hours_per_month' => 160
        ]);
        $employee->positions()->attach($position->id);


        // Przygotowujemy dane (Payload), które normalnie wpisałbyś w Postmanie
        $payload = [
            'user_id' => $employee->id,
            'position_id' => $position->id,
            'date' => '2025-12-08',
            'shift_start' => '08:00',
            'shift_end' => '16:00', // To powinno dać 8h = 480 minut
        ];

        // 2. ACT (Akcja)
        // actingAs($manager) -> Symuluje zalogowanie (generuje JWT/session pod spodem)
        // postJson -> Wysyła POST z nagłówkiem Accept: application/json
        $response = $this->actingAs($manager)
            ->postJson('/api/schedules', $payload);

        // 3. ASSERT (Sprawdzenie Wyniku)
        // Sprawdź czy status HTTP to 201 (Created)
        $response->assertStatus(201);

        // Sprawdź czy baza danych zawiera taki rekord
        // Laravel sam sprawdzi tabelę 'schedules'
        $this->assertDatabaseHas('schedules', [
            'user_id' => $employee->id,
            'date' => '2025-12-08',
            'hours_worked' => 480, // Sprawdzamy czy Twój kalkulator minut działa!
            'status' => 'scheduled'
        ]);
    }
    public function test_manager_can_update_schedule()
    {
        $this->withoutExceptionHandling();
        $manager =  User::factory()->create(['role' => 'manager']);
        $position = Position::create([
            'name' => 'B2',
            'description' => 'Bileter drugi',
        ]);
        $position2 = Position::create([
            'name' => 'K1',
            'description' => 'Koordynator Tour Guide',
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'max_hours_per_month' => 200
        ]);
        $employee->positions()->attach($position->id);


        $schedule = Schedule::create([
            'user_id' => $employee->id,
            'position_id' => $position->id,
            'date' => '2025-10-08',
            'shift_start' => '10:00',
            'shift_end' => '18:00',
        ]);

        $data = [


            'shift_start' => '08:00',
            'shift_end' => '16:00',
        ];

        $response = $this->actingAs($manager)->patchJson("/api/schedules/{$schedule->id}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'shift_start' => '08:00',
            'hours_worked' => 480
        ]);
    }
    public function test_manager_can_delete_schedule()
    {

        $manager = User::factory()->create(['role' => 'manager']);
        $position = Position::create([
            'name' => 'B2',
            'description' => 'Bileter drugi',
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'max_hours_per_month' => 200
        ]);
        $employee->positions()->attach($position->id);

        $schedule = Schedule::create([
            'user_id' => $employee->id,
            'position_id' => $position->id,
            'date' => '2025-10-08',
            'shift_start' => '10:00',
            'shift_end' => '18:00',
        ]);
        $response = $this->actingAs($manager)
            ->deleteJson("/api/schedules/{$schedule->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }
}
