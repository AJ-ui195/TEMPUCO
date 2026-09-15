<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class MemberPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function view(Authenticatable $actor, Member $member): bool
    {
        if ($actor instanceof User && $actor->isAdmin()) {
            return true;
        }

        return $actor instanceof Member && (int) $actor->id === (int) $member->id;
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function update(Authenticatable $actor, Member $member): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function delete(Authenticatable $actor, Member $member): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }
}
