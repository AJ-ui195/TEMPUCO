<?php

namespace App\Support;

use App\Models\Member;

/**
 * Canonical loan_type string values used across ledgers and filters.
 */
final class LoanTypes
{
    public const QUICK = 'QUICK LOAN';

    public const CHARACTER = 'CHARACTER LOAN';

    public const CHARACTER_EMERGENCY = 'CHARACTER-EMERGENCY LOAN';

    public const CHARACTER_SHORT_TERM = 'CHARACTER SHORT-TERM LOAN';

    public const EMERGENCY = 'EMERGENCY LOAN';

    public const CALAMITY = 'CALAMITY LOAN';

    public const RETIREE_SHORT_TERM = 'RETIREE SHORT-TERM LOAN';

    public const TRAVEL = 'TRAVEL LOAN';

    public const REGULAR = 'REGULAR LOAN';

    public const SALARY_2 = 'SALARY LOAN 2';

    public const COLLATERALIZED = 'COLLATERALIZED LOAN';

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
            self::CHARACTER_EMERGENCY,
            self::CHARACTER_SHORT_TERM,
            self::EMERGENCY,
            self::CALAMITY,
            self::RETIREE_SHORT_TERM,
            self::TRAVEL,
            self::COLLATERALIZED,
        ];
    }

    /**
     * Character and emergency loans share `character_loans`.
     *
     * @return list<string>
     */
    public static function characterFamily(): array
    {
        return [
            self::CHARACTER,
            self::CHARACTER_EMERGENCY,
            self::CHARACTER_SHORT_TERM,
            self::EMERGENCY,
            self::CALAMITY,
            self::RETIREE_SHORT_TERM,
            self::TRAVEL,
            self::COLLATERALIZED,
        ];
    }

    public static function isCharacterFamily(string $loanType): bool
    {
        $normalized = strtoupper(trim($loanType));

        foreach (self::characterFamily() as $type) {
            if (strcasecmp($normalized, $type) === 0) {
                return true;
            }
        }

        return false;
    }

    public static function isCharacter(string $loanType): bool
    {
        return strcasecmp(trim($loanType), self::CHARACTER) === 0;
    }

    public static function isCharacterEmergency(string $loanType): bool
    {
        return strcasecmp(trim($loanType), self::CHARACTER_EMERGENCY) === 0;
    }

    public static function isCharacterShortTerm(string $loanType): bool
    {
        return strcasecmp(trim($loanType), self::CHARACTER_SHORT_TERM) === 0;
    }

    public static function isEmergency(string $loanType): bool
    {
        return strcasecmp(trim($loanType), self::EMERGENCY) === 0;
    }

    public static function isCalamity(string $loanType): bool
    {
        return strcasecmp(trim($loanType), self::CALAMITY) === 0;
    }

    public static function isRetireeShortTerm(string $loanType): bool
    {
        return strcasecmp(trim($loanType), self::RETIREE_SHORT_TERM) === 0;
    }

    public static function isTravel(string $loanType): bool
    {
        return strcasecmp(trim($loanType), self::TRAVEL) === 0;
    }

    /**
     * Type-of-loan options on the character application form.
     *
     * @return array<string, string>
     */
    public static function characterOptions(?Member $member = null): array
    {
        $options = [
            self::CHARACTER => __('Character loan'),
            self::CHARACTER_SHORT_TERM => __('Short term'),
            self::CALAMITY => __('Calamity loan'),
            self::EMERGENCY => __('Emergency loan'),
            self::TRAVEL => __('Travel loan'),
            self::COLLATERALIZED => __('Collateralized loan'),
        ];

        if ($member?->isRetiree()) {
            $options[self::RETIREE_SHORT_TERM] = __('Retirees’ short-term loan');
        }

        return $options;
    }

    public static function isSalary2(string $loanType): bool
    {
        return strcasecmp(trim($loanType), self::SALARY_2) === 0;
    }

    public static function isCollateralized(string $loanType): bool
    {
        return strcasecmp(trim($loanType), self::COLLATERALIZED) === 0;
    }

    /**
     * Types that must not count toward first/second APDS accounts.
     *
     * @return list<string>
     */
    public static function excludedFromApdsAccounts(): array
    {
        return [
            ...self::nonRegular(),
            self::COLLATERALIZED,
        ];
    }

    /**
     * Type-of-loan options on the regular application form.
     *
     * @return array<string, string>
     */
    public static function regularOptions(): array
    {
        return [
            self::REGULAR => __('Salary loan 1'),
            self::SALARY_2 => __('Salary loan 2'),
        ];
    }
}
