<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            ['name' => 'PD',   'description' => 'Dispatcher Assistant'],

            ['name' => 'B1',  'description' => 'Ticketing Officer 1'],
            ['name' => 'B2',  'description' => 'Ticketing Officer 2'],
            ['name' => 'B3',  'description' => 'Ticketing Officer 3'],
            ['name' => 'B4',  'description' => 'Ticketing Officer 4'],
            ['name' => 'B5',  'description' => 'Ticketing Officer 5'],
            ['name' => 'B6',  'description' => 'Ticketing Officer 6'],
            ['name' => 'B7',  'description' => 'Ticketing Officer 7'],
            ['name' => 'B8',  'description' => 'Ticketing Officer 8'],

            ['name' => 'PW',  'description' => 'Wisła Route Assistant'],
            ['name' => 'PW2', 'description' => 'Wisła Route Assistant 2'],

            ['name' => 'WR',  'description' => 'Regis Shaft Lift Operator'],
            ['name' => 'WR2', 'description' => 'Regis Shaft Lift Operator 2'],
            ['name' => 'WR3', 'description' => 'Regis Shaft Lift Operator 3'],

            ['name' => 'WS',  'description' => 'Staszic Shaft Lift Operator'],
            ['name' => 'WS2', 'description' => 'Staszic Shaft Lift Operator 2'],

            ['name' => 'SR',  'description' => 'Regis Cloakroom Attendant'],

            ['name' => 'K1',  'description' => 'Tour Guide Coordinator 1'],
            ['name' => 'K2',  'description' => 'Tour Guide Coordinator 2'],

            ['name' => 'TGT', 'description' => 'Group Tour Check-in Desk'],
            ['name' => 'TG',  'description' => 'Individual Tour Check-in Desk'],

            ['name' => 'PTG',  'description' => 'Tour Guide Assistant'],
            ['name' => 'PTG2', 'description' => 'Tour Guide Assistant 2'],

            ['name' => 'OTG',  'description' => 'Tour Guide Pick-up Desk'],
            ['name' => 'OTG2', 'description' => 'Tour Guide Pick-up Desk 2'],

            ['name' => 'BT',  'description' => 'Brine Tower Ticketing Officer'],
        ];
        foreach($positions as $data){
            Position::firstOrCreate(
                ['name' => $data['name']],
                ['description' => $data['description']]
            );
        }
    }
}
