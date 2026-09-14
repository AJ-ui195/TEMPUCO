<?php

namespace App\Support;

use App\Models\Member;

final class CollateralizedLoanRules
{
    public const MAX_AMOUNT = 300000.0;

    public const MIN_TERM_MONTHS = 12;

    public const MAX_TERM_MONTHS = 24;

    public const MONTHLY_RATE_REGULAR_MEMBER = 0.02;

    public const MONTHLY_RATE_RETIREE = 0.01;

    public static function monthlyInterestRate(Member $member): float
    {
        return $member->isRetiree()
            ? self::MONTHLY_RATE_RETIREE
            : self::MONTHLY_RATE_REGULAR_MEMBER;
    }

    public static function amountError(float $amount): ?string
    {
        if ($amount > self::MAX_AMOUNT) {
            return __('The maximum collateralized loan is ₱:max.', [
                'max' => number_format(self::MAX_AMOUNT, 2),
            ]);
        }

        return null;
    }

    public static function termError(int $termMonths): ?string
    {
        if ($termMonths < self::MIN_TERM_MONTHS || $termMonths > self::MAX_TERM_MONTHS) {
            return __('The collateralized loan term must be 12 to 24 months.');
        }

        return null;
    }
}
