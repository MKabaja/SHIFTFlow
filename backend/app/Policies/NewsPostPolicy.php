<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NewsPost;
use App\Models\User;

class NewsPostPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, NewsPost $newsPost): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, NewsPost $newsPost): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, NewsPost $newsPost): bool
    {
        return $user->role === 'admin';
    }
}
