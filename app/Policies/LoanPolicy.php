<?php

namespace App\Policies;

use App\Enums\LoanStatus;
use App\Models\Contracts\MemberLoan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class LoanPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function view(Authenticatable $actor, MemberLoan $loan): bool
    {
        if ($actor instanceof User && $actor->isAdmin()) {
            return true;
        }

        return $actor instanceof Member && (int) $actor->id === (int) $loan->member_id;
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof Member && $actor->isActive();
    }

    public function update(Authenticatable $actor, MemberLoan $loan): bool
    {
        return $actor instanceof User && $actor->isAdmin();
    }

    public function delete(Authenticatable $actor, MemberLoan $loan): bool
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

    public function approve(Authenticatable $actor, MemberLoan $loan): bool
    {
        return $actor instanceof User
            && $actor->isAdmin()
            && $loan->status === LoanStatus::Pending;
    }

    public function reject(Authenticatable $actor, MemberLoan $loan): bool
    {
        return $this->approve($actor, $loan);
    }

    public function recordPayment(Authenticatable $actor, MemberLoan $loan): bool
    {
        return $actor instanceof User
            && $actor->isAdmin()
            && $loan->status === LoanStatus::Approved;
    }
}
