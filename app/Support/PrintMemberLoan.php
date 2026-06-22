<?php

namespace App\Support;

use App\Models\Loan;

final class PrintMemberLoan
{
    public static function printUrl(Loan $loan, bool $autoPrint = false): string
    {
        return route('members.loans.print', [
            'loan' => $loan,
            'auto' => $autoPrint ? 1 : 0,
        ]);
    }

    /**
     * @return array{loan: Loan, autoPrint: bool}
     */
    public static function viewData(Loan $loan, bool $autoPrint = false): array
    {
        $loan->loadMissing(['user', 'certification', 'committeeDecision', 'payments']);

        return [
            'loan' => $loan,
            'autoPrint' => $autoPrint,
        ];
    }
}
