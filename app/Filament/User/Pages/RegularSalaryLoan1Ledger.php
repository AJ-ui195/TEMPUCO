<?php

namespace App\Filament\User\Pages;

class RegularSalaryLoan1Ledger extends RegularSalarySchedulePage
{
    protected static ?string $navigationLabel = 'Salary loan 1 (APDS schedule)';

    protected static ?string $title = 'Salary loan 1';

    protected static ?string $slug = 'loan-ledger/regular-loan/salary-1';

    protected static ?int $navigationSort = 1;

    protected static bool $isDiscovered = true;

    protected static function isSalaryTwo(): bool
    {
        return false;
    }
}
