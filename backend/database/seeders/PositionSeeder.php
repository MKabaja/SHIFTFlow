<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'PD',   'description' => 'Dispatcher Assistant',        'color' => '#F7C58A'],

            ['name' => 'B1',   'description' => 'Ticketing Officer 1',          'color' => '#FFF200'],
            ['name' => 'B2',   'description' => 'Ticketing Officer 2',          'color' => '#FFF200'],
            ['name' => 'B3',   'description' => 'Ticketing Officer 3',          'color' => '#FFF200'],
            ['name' => 'B4',   'description' => 'Ticketing Officer 4',          'color' => '#FFF200'],
            ['name' => 'B5',   'description' => 'Ticketing Officer 5',          'color' => '#FFF200'],
            ['name' => 'B6',   'description' => 'Ticketing Officer 6',          'color' => '#FFF200'],
            ['name' => 'B7',   'description' => 'Ticketing Officer 7',          'color' => '#FFF200'],
            ['name' => 'B8',   'description' => 'Ticketing Officer 8',          'color' => '#FFF200'],

            ['name' => 'PW',   'description' => 'Wisła Route Assistant',        'color' => '#7DB7FF'],
            ['name' => 'PW2',  'description' => 'Wisła Route Assistant 2',      'color' => '#7DB7FF'],

            ['name' => 'WR',   'description' => 'Regis Shaft Lift Operator',    'color' => '#D5E4FF'],
            ['name' => 'WR2',  'description' => 'Regis Shaft Lift Operator 2',  'color' => '#D5E4FF'],
            ['name' => 'WR3',  'description' => 'Regis Shaft Lift Operator 3',  'color' => '#D5E4FF'],

            ['name' => 'WS',   'description' => 'Staszic Shaft Lift Operator',  'color' => '#9CC8FF'],
            ['name' => 'WS2',  'description' => 'Staszic Shaft Lift Operator 2', 'color' => '#9CC8FF'],

            ['name' => 'SR',   'description' => 'Regis Cloakroom Attendant',    'color' => '#E9C7FF'],

            ['name' => 'K1',   'description' => 'Tour Guide Coordinator 1',     'color' => '#DFA97A'],
            ['name' => 'K2',   'description' => 'Tour Guide Coordinator 2',     'color' => '#DFA97A'],

            ['name' => 'TGT',  'description' => 'Group Tour Check-in Desk',     'color' => '#87F875'],
            ['name' => 'TG',   'description' => 'Individual Tour Check-in Desk', 'color' => '#87F875'],

            ['name' => 'PTG',  'description' => 'Tour Guide Assistant',         'color' => '#A3EBE7'],
            ['name' => 'PTG2', 'description' => 'Tour Guide Assistant 2',       'color' => '#A3EBE7'],

            ['name' => 'OTG',  'description' => 'Tour Guide Pick-up Desk',      'color' => '#C4EDFF'],
            ['name' => 'OTG2', 'description' => 'Tour Guide Pick-up Desk 2',    'color' => '#C4EDFF'],

            ['name' => 'BT',   'description' => 'Brine Tower Ticketing Officer', 'color' => '#E8B4FF'],

            ['name' => 'U',    'description' => 'Employee Leave',               'color' => '#649837'],
        ];

        foreach ($positions as $data) {
            Position::updateOrCreate(
                ['name' => $data['name']],
                ['description' => $data['description'], 'color' => $data['color']]
            );
        }
    }
}
