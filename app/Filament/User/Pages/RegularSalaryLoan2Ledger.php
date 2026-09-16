<?php

namespace App\Filament\User\Pages;

class RegularSalaryLoan2Ledger extends RegularSalarySchedulePage
{
    protected static ?string $navigationLabel = 'Salary loan 2 (APDS schedule)';

    protected static ?string $title = 'Salary loan 2';

    protected static ?string $slug = 'loan-ledger/regular-loan/salary-2';

    protected static ?int $navigationSort = 2;

    protected static bool $isDiscovered = true;

    protected static function isSalaryTwo(): bool
    {
        return true;
    }
}
