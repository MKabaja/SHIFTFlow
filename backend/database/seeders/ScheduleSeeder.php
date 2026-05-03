<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('login', 'admin')->first();

        Schedule::firstOrCreate(
            ['name' => 'April 2026 Schedule'],
            [
                'description' => 'Schedule for April 2026',
                'month' => 4,
                'year' => 2026,
                'status' => 'published',
                'published_at' => now(),
                'created_by' => $admin?->id,
            ]
        );

        Schedule::firstOrCreate(
            ['name' => 'May 2026 Schedule'],
            [
                'description' => 'Schedule for May 2026',
                'month' => 5,
                'year' => 2026,
                'status' => 'draft',
                'published_at' => null,
                'created_by' => $admin?->id,
            ]
        );
    }
}
