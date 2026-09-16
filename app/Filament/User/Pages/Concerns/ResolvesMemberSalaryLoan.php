<?php

namespace App\Filament\User\Pages\Concerns;

use App\Enums\LoanStatus;
use App\Models\Member;
use App\Models\RegularLoan;
use App\Support\LoanTypes;

trait ResolvesMemberSalaryLoan
{
    protected function latestSalaryLoan(Member $user, bool $salaryTwo): ?RegularLoan
    {
        $query = RegularLoan::query()
            ->forUser($user)
            ->where('status', LoanStatus::Approved)
            ->with('payments')
            ->orderedByLoanDate()
            ->orderByDesc('id');

        if ($salaryTwo) {
            $query->whereRaw('UPPER(TRIM(loan_type)) = ?', [LoanTypes::SALARY_2]);
        } else {
            $query->where(function ($inner): void {
                $inner->whereNull('loan_type')
                    ->orWhereRaw('UPPER(TRIM(loan_type)) <> ?', [LoanTypes::SALARY_2]);
            });
        }

        return $query->first();
    }
}
