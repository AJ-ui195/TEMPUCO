<?php

namespace App\Filament\User\Pages;

class IndividualSalaryLoan1Ledger extends IndividualSalaryLedgerPage
{
    protected static ?string $navigationLabel = 'Salary loan 1 (paper card)';

    protected static ?string $title = 'Salary loan 1';

    protected static ?string $slug = 'loan-ledger/individual-ledger/salary-1';

    protected static ?int $navigationSort = 1;

    protected static bool $isDiscovered = true;

    protected static function isSalaryTwo(): bool
    {
        return false;
    }
}
