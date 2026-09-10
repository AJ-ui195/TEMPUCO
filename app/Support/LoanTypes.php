<?php

namespace App\Support;

/**
 * Canonical loan_type string values used across ledgers and filters.
 */
final class LoanTypes
{
    public const QUICK = 'QUICK LOAN';

    public const CHARACTER = 'CHARACTER LOAN';

    public const EMERGENCY = 'EMERGENCY LOAN';

    /**
     * Special ledger types that must not appear under Regular loan.
     *
     * @return list<string>
     */
    public static function nonRegular(): array
    {
        return [
            self::QUICK,
            self::CHARACTER,
            self::EMERGENCY,
        ];
    }
}
