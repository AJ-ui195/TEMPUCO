<?php

namespace App\Policies;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class LoanPaymentPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function view(Authenticatable $actor, LoanPayment $payment): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function create(Authenticatable $actor, ?Loan $loan = null): bool
    {
        if (! ($actor instanceof User && $actor->isAdmin())) {
            return false;
        }

        if ($loan === null) {
            return true;
        }

        return $loan->status === LoanStatus::Approved;
    }

    public function update(Authenticatable $actor, LoanPayment $payment): bool
    {
        return false;
    }

    public function delete(Authenticatable $actor, LoanPayment $payment): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }
}
