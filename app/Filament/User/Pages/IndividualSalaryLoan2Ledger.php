<?php

namespace App\Filament\User\Pages;

class IndividualSalaryLoan2Ledger extends IndividualSalaryLedgerPage
{
    protected static ?string $navigationLabel = 'Salary loan 2 (paper card)';

    protected static ?string $title = 'Salary loan 2';

    protected static ?string $slug = 'loan-ledger/individual-ledger/salary-2';

    protected static ?int $navigationSort = 2;

    protected static bool $isDiscovered = true;

    protected static function isSalaryTwo(): bool
    {
        return true;
    }
}
