<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'login' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        User::factory()->manager()->create([
            'name' => 'Anna Kowalska',
            'login' => 'akowal',
            'email' => 'akowal@example.com',
            'password' => 'password',
        ]);

        User::factory()->employee()->create([
            'name' => 'Tomasz Nowak',
            'login' => 'tnowak',
            'email' => 'tnowak@example.com',
            'pin_hashed' => '1234',
        ]);

        User::factory()->employee()->create([
            'name' => 'Jan Wiśniewski',
            'login' => 'jwisn',
            'pin_hashed' => '1111',
        ]);

        User::factory()->employee()->create([
            'name' => 'Katarzyna Nowak',
            'login' => 'knowak',
            'pin_hashed' => '2222',
        ]);

        User::factory()->employee()->create([
            'name' => 'Piotr Zając',
            'login' => 'pzajac',
            'pin_hashed' => '3333',
        ]);

        User::factory()->employee()->create([
            'name' => 'Agnieszka Dąbrowska',
            'login' => 'adabrow',
            'pin_hashed' => '4444',
        ]);

        $this->call([
            PositionSeeder::class,
            ScheduleSeeder::class,
            ShiftSeeder::class,
            NewsPostSeeder::class,
        ]);
    }
}
