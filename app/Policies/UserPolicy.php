<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class UserPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function view(Authenticatable $actor, User $user): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function update(Authenticatable $actor, User $user): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function delete(Authenticatable $actor, User $user): bool
    {
        if (! ($actor instanceof User && $actor->isAdmin())) {
            return false;
        }

        if ($user->isAdmin() && $this->adminCount() <= 1) {
            return false;
        }

        return true;
    }

    public function deleteAny(Authenticatable $actor): bool
    {
        return false;
    }

    private function adminCount(): int
    {
        return User::query()->where('role', UserRole::Admin)->count();
    }
}
