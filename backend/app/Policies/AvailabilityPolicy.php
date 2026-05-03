<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Availability;
use App\Models\User;

class AvailabilityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Availability $availability): bool
    {
        return $user->id === $availability->user_id || in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'employee']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Availability $availability): bool
    {
        return $user->id === $availability->user_id || $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Availability $availability): bool
    {
        return $user->id === $availability->user_id || $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Availability $availability): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Availability $availability): bool
    {
        return false;
    }
}
