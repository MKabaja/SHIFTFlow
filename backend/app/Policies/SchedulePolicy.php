<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Schedule $schedule): bool
    {
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        return $schedule->status === 'published';
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }
}
