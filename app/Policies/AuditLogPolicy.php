<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class AuditLogPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function view(Authenticatable $actor, AuditLog $auditLog): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function create(Authenticatable $actor): bool
    {
        return false;
    }

    public function update(Authenticatable $actor, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(Authenticatable $actor, AuditLog $auditLog): bool
    {
        return false;
    }
}
