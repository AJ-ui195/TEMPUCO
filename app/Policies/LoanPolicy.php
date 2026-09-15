<?php

namespace App\Policies;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class LoanPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function view(Authenticatable $actor, Loan $loan): bool
    {
        if ($actor instanceof User && $actor->isAdmin()) {
            return true;
        }

        return $actor instanceof Member && (int) $actor->id === (int) $loan->user_id;
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof Member && $actor->isActive();
    }

    public function update(Authenticatable $actor, Loan $loan): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function delete(Authenticatable $actor, Loan $loan): bool
    {
        return $actor instanceof User
            && $actor->isAdmin()
            && in_array($loan->status, [
                LoanStatus::Pending,
                LoanStatus::AwaitingVerification,
            ], true);
    }

    public function deleteAny(Authenticatable $actor): bool
    {
        return false;
    }

    public function approve(Authenticatable $actor, Loan $loan): bool
    {
        return $actor instanceof User
            && $actor->isAdmin()
            && $loan->status === LoanStatus::Pending;
    }

    public function reject(Authenticatable $actor, Loan $loan): bool
    {
        return $this->approve($actor, $loan);
    }

    public function recordPayment(Authenticatable $actor, Loan $loan): bool
    {
        return $actor instanceof User
            && $actor->isAdmin()
            && $loan->status === LoanStatus::Approved;
    }
}
