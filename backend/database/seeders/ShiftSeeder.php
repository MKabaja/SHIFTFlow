<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $schedule = Schedule::where('name', 'April 2026 Schedule')->first();

        if (! $schedule) {
            return;
        }

        $employeeShifts = [
            'tnowak' => [
                'positions' => ['B1', 'PW'],
                'shifts' => [
                    ['date' => '2026-04-07', 'position' => 'B1', 'start' => '08:00', 'end' => '16:00', 'minutes' => 480],
                    ['date' => '2026-04-09', 'position' => 'PW', 'start' => '09:00', 'end' => '17:00', 'minutes' => 480],
                    ['date' => '2026-04-14', 'position' => 'B1', 'start' => '07:00', 'end' => '15:00', 'minutes' => 480],
                    ['date' => '2026-04-16', 'position' => 'PW', 'start' => '08:00', 'end' => '14:00', 'minutes' => 360],
                    ['date' => '2026-04-22', 'position' => 'B1', 'start' => '10:00', 'end' => '18:00', 'minutes' => 480],
                    ['date' => '2026-04-24', 'position' => 'PW', 'start' => '09:00', 'end' => '17:00', 'minutes' => 480],
                ],
            ],
            'jwisn' => [
                'positions' => ['B2', 'B3'],
                'shifts' => [
                    ['date' => '2026-04-01', 'position' => 'B2', 'start' => '08:00', 'end' => '16:00', 'minutes' => 480],
                    ['date' => '2026-04-03', 'position' => 'B3', 'start' => '09:00', 'end' => '17:00', 'minutes' => 480],
                    ['date' => '2026-04-08', 'position' => 'B2', 'start' => '07:00', 'end' => '15:00', 'minutes' => 480],
                    ['date' => '2026-04-15', 'position' => 'B3', 'start' => '08:00', 'end' => '16:00', 'minutes' => 480],
                    ['date' => '2026-04-22', 'position' => 'B2', 'start' => '10:00', 'end' => '18:00', 'minutes' => 480],
                ],
            ],
            'knowak' => [
                'positions' => ['WR', 'WR2'],
                'shifts' => [
                    ['date' => '2026-04-02', 'position' => 'WR',  'start' => '08:00', 'end' => '16:00', 'minutes' => 480],
                    ['date' => '2026-04-04', 'position' => 'WR2', 'start' => '09:00', 'end' => '17:00', 'minutes' => 480],
                    ['date' => '2026-04-09', 'position' => 'WR',  'start' => '06:00', 'end' => '14:00', 'minutes' => 480],
                    ['date' => '2026-04-17', 'position' => 'WR2', 'start' => '08:00', 'end' => '16:00', 'minutes' => 480],
                    ['date' => '2026-04-23', 'position' => 'WR',  'start' => '07:00', 'end' => '15:00', 'minutes' => 480],
                ],
            ],
            'pzajac' => [
                'positions' => ['WS', 'SR'],
                'shifts' => [
                    ['date' => '2026-04-01', 'position' => 'WS', 'start' => '07:00', 'end' => '15:00', 'minutes' => 480],
                    ['date' => '2026-04-05', 'position' => 'SR', 'start' => '09:00', 'end' => '17:00', 'minutes' => 480],
                    ['date' => '2026-04-10', 'position' => 'WS', 'start' => '08:00', 'end' => '16:00', 'minutes' => 480],
                    ['date' => '2026-04-18', 'position' => 'SR', 'start' => '10:00', 'end' => '18:00', 'minutes' => 480],
                    ['date' => '2026-04-24', 'position' => 'WS', 'start' => '08:00', 'end' => '16:00', 'minutes' => 480],
                ],
            ],
            'adabrow' => [
                'positions' => ['TGT', 'TG'],
                'shifts' => [
                    ['date' => '2026-04-02', 'position' => 'TGT', 'start' => '08:00', 'end' => '16:00', 'minutes' => 480],
                    ['date' => '2026-04-07', 'position' => 'TG',  'start' => '09:00', 'end' => '17:00', 'minutes' => 480],
                    ['date' => '2026-04-11', 'position' => 'TGT', 'start' => '08:00', 'end' => '16:00', 'minutes' => 480],
                    ['date' => '2026-04-18', 'position' => 'TG',  'start' => '07:00', 'end' => '15:00', 'minutes' => 480],
                    ['date' => '2026-04-25', 'position' => 'TGT', 'start' => '10:00', 'end' => '18:00', 'minutes' => 480],
                ],
            ],
        ];

        $positionCache = [];

        foreach ($employeeShifts as $login => $config) {
            $employee = User::where('login', $login)->first();

            if (! $employee) {
                continue;
            }

            $positionIds = [];
            foreach ($config['positions'] as $posName) {
                if (! isset($positionCache[$posName])) {
                    $positionCache[$posName] = Position::where('name', $posName)->first();
                }
                if ($positionCache[$posName]) {
                    $positionIds[] = $positionCache[$posName]->id;
                }
            }

            $employee->positions()->syncWithoutDetaching($positionIds);

            foreach ($config['shifts'] as $shiftData) {
                $position = $positionCache[$shiftData['position']] ?? null;

                if (! $position) {
                    continue;
                }

                Shift::firstOrCreate(
                    [
                        'user_id' => $employee->id,
                        'schedule_id' => $schedule->id,
                        'date' => $shiftData['date'],
                        'position_id' => $position->id,
                    ],
                    [
                        'shift_start' => $shiftData['start'],
                        'shift_end' => $shiftData['end'],
                        'minutes_worked' => $shiftData['minutes'],
                        'status' => 'scheduled',
                    ]
                );
            }
        }
    }
}
