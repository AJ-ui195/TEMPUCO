<?php

namespace App\Concerns;

trait HasAccountStatus
{
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function mustChangePassword(): bool
    {
        return (bool) $this->must_change_password;
    }
}
