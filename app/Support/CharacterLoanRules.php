<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\CharacterLoan;
use App\Models\Member;

final class CharacterLoanRules
{
    public const VARIANT_EMERGENCY = 'character_emergency';

    public const VARIANT_SHORT_TERM = 'character_short_term';

    public const CALAMITY_MAX_AMOUNT = 40000.0;

    public const CALAMITY_TERM_MONTHS = 24;

    public const CALAMITY_ANNUAL_RATE = 0.05;

    public const RETIREE_SHORT_TERM_MIN_MONTHS = 12;

    public const RETIREE_SHORT_TERM_MAX_MONTHS = 24;

    public const RETIREE_SHORT_TERM_MONTHLY_RATE = 0.01;

    public const EMERGENCY_TERM_MONTHS = 3;

    public const EMERGENCY_MONTHLY_RATE = 0.01;

    public const EMERGENCY_MAX_ORIGINALS_PLUS_RENEWALS = 3;

    public const CHARACTER_MONTHLY_RATE = 0.02;

    public const CHARACTER_MONTHLY_RATE_RETIREE = 0.01;

    public const CHARACTER_EMERGENCY_TERM_MONTHS = 3;

    public const CHARACTER_SHORT_TERM_MONTHS = 12;

    public const CHARACTER_WITH_APDS_MAX = 50000.0;

    public const CHARACTER_WITHOUT_APDS_MAX = 100000.0;

    public const TRAVEL_MAX_AMOUNT = 70000.0;

    public const TRAVEL_TERM_MONTHS = 6;

    public const TRAVEL_RATE_REGULAR = 0.02;

    public const TRAVEL_RATE_RETIREE = 0.01;

    public static function resolveStoredType(string $dropdownType, ?string $variant): string
    {
        if (! LoanTypes::isCharacter($dropdownType)) {
            return $dropdownType;
        }

        return $variant === self::VARIANT_SHORT_TERM
            ? LoanTypes::CHARACTER_SHORT_TERM
            : LoanTypes::CHARACTER_EMERGENCY;
    }

    public static function characterMonthlyRate(bool $isRetiree): float
    {
        return $isRetiree
            ? self::CHARACTER_MONTHLY_RATE_RETIREE
            : self::CHARACTER_MONTHLY_RATE;
    }

    public static function defaultTermMonths(string $loanType): int
    {
        return match (true) {
            LoanTypes::isCalamity($loanType) => self::CALAMITY_TERM_MONTHS,
            LoanTypes::isEmergency($loanType), LoanTypes::isCharacterEmergency($loanType) => self::CHARACTER_EMERGENCY_TERM_MONTHS,
            LoanTypes::isTravel($loanType) => self::TRAVEL_TERM_MONTHS,
            LoanTypes::isCharacterShortTerm($loanType) => self::CHARACTER_SHORT_TERM_MONTHS,
            LoanTypes::isRetireeShortTerm($loanType), LoanTypes::isCollateralized($loanType) => self::RETIREE_SHORT_TERM_MIN_MONTHS,
            default => 3,
        };
    }

    public static function termIsLocked(string $loanType): bool
    {
        return LoanTypes::isCalamity($loanType)
            || LoanTypes::isEmergency($loanType)
            || LoanTypes::isTravel($loanType)
            || LoanTypes::isCharacterEmergency($loanType)
            || LoanTypes::isCharacterShortTerm($loanType)
            || LoanTypes::isCharacter($loanType);
    }

    public static function periodInterest(string $loanType, float $amount, int $termMonths, bool $isRetiree): float
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return 0.0;
        }

        $months = max(1, $termMonths);

        $rate = self::characterMonthlyRate($isRetiree);

        return match (true) {
            LoanTypes::isCalamity($loanType) => round($amount * self::CALAMITY_ANNUAL_RATE * ($months / 12), 2),
            LoanTypes::isEmergency($loanType) => round($amount * self::EMERGENCY_MONTHLY_RATE * $months, 2),
            LoanTypes::isCharacterEmergency($loanType) => round($amount * $rate * self::CHARACTER_EMERGENCY_TERM_MONTHS, 2),
            LoanTypes::isCharacterShortTerm($loanType) => round($amount * $rate, 2),
            LoanTypes::isTravel($loanType) => round($amount * ($isRetiree ? self::TRAVEL_RATE_RETIREE : self::TRAVEL_RATE_REGULAR), 2),
            LoanTypes::isRetireeShortTerm($loanType) => round($amount * self::RETIREE_SHORT_TERM_MONTHLY_RATE, 2),
            LoanTypes::isCollateralized($loanType) => RegularLoanSchedule::calculateCollateralized(
                $amount,
                $months,
                $isRetiree,
            )->monthlyInstallment,
            default => round($amount * $rate * min($months, 3), 2),
        };
    }

    public static function characterMaxAmount(Member $member): float
    {
        if (ApdsRules::hasQualifyingApdsForCharacter($member)) {
            return self::CHARACTER_WITH_APDS_MAX;
        }

        return self::CHARACTER_WITHOUT_APDS_MAX;
    }

    public static function hasOpenCharacterEmergency(Member $member): bool
    {
        $loans = CharacterLoan::query()
            ->forUser($member)
            ->whereRaw('UPPER(TRIM(loan_type)) = ?', [LoanTypes::CHARACTER_EMERGENCY])
            ->whereIn('status', [LoanStatus::Pending, LoanStatus::Approved])
            ->with('payments')
            ->get();

        foreach ($loans as $loan) {
            if ($loan->status === LoanStatus::Pending) {
                return true;
            }

            if (RecordMemberLoanPayment::remainingPrincipal($loan) > RecordMemberLoanPayment::EPSILON) {
                return true;
            }
        }

        return false;
    }

    public static function applicationError(
        Member $member,
        string $loanType,
        float $amount,
        int $termMonths,
    ): ?string {
        if (! LoanTypes::isCharacterFamily($loanType) || LoanTypes::isCharacter($loanType)) {
            return LoanTypes::isCharacter($loanType)
                ? __('Choose Character-Emergency or Character Short-Term.')
                : __('Choose a character loan type.');
        }

        if (LoanTypes::isCollateralized($loanType)) {
            return CollateralizedLoanRules::amountError($amount)
                ?? CollateralizedLoanRules::termError($termMonths);
        }

        if (LoanTypes::isRetireeShortTerm($loanType) && ! $member->isRetiree()) {
            return __('The retirees’ short-term loan is available only to retired members.');
        }

        if (LoanTypes::isCharacterEmergency($loanType) || LoanTypes::isCharacterShortTerm($loanType)) {
            $max = self::characterMaxAmount($member);

            if ($amount > $max) {
                return ApdsRules::hasQualifyingApdsForCharacter($member)
                    ? __('With an existing APDS loan of at least ₱300,000, the character loan maximum is ₱:max.', [
                        'max' => number_format(self::CHARACTER_WITH_APDS_MAX, 2),
                    ])
                    : __('Without an APDS loan, the character loan maximum is 90% of share capital or ₱:max, whichever is lower.', [
                        'max' => number_format(self::CHARACTER_WITHOUT_APDS_MAX, 2),
                    ]);
            }
        }

        if (LoanTypes::isCharacterShortTerm($loanType) && self::hasOpenCharacterEmergency($member)) {
            return __('Settle the existing Character-Emergency loan before applying for a Character Short-Term loan.');
        }

        if (LoanTypes::isCalamity($loanType) && $amount > self::CALAMITY_MAX_AMOUNT) {
            return __('Calamity loans may not exceed ₱:max.', [
                'max' => number_format(self::CALAMITY_MAX_AMOUNT, 2),
            ]);
        }

        if (LoanTypes::isTravel($loanType) && $amount > self::TRAVEL_MAX_AMOUNT) {
            return __('Travel loans may not exceed ₱:max.', [
                'max' => number_format(self::TRAVEL_MAX_AMOUNT, 2),
            ]);
        }

        if (LoanTypes::isCalamity($loanType) && $termMonths !== self::CALAMITY_TERM_MONTHS) {
            return __('The calamity loan term is :months months.', [
                'months' => self::CALAMITY_TERM_MONTHS,
            ]);
        }

        if (LoanTypes::isEmergency($loanType) && $termMonths !== self::EMERGENCY_TERM_MONTHS) {
            return __('The emergency loan term is :months months.', [
                'months' => self::EMERGENCY_TERM_MONTHS,
            ]);
        }

        if (LoanTypes::isCharacterEmergency($loanType) && $termMonths !== self::CHARACTER_EMERGENCY_TERM_MONTHS) {
            return __('The Character-Emergency term is :months months.', [
                'months' => self::CHARACTER_EMERGENCY_TERM_MONTHS,
            ]);
        }

        if (LoanTypes::isCharacterShortTerm($loanType) && $termMonths !== self::CHARACTER_SHORT_TERM_MONTHS) {
            return __('The Character Short-Term loan term is :months months.', [
                'months' => self::CHARACTER_SHORT_TERM_MONTHS,
            ]);
        }

        if (LoanTypes::isTravel($loanType) && $termMonths !== self::TRAVEL_TERM_MONTHS) {
            return __('The travel loan term is :months months.', [
                'months' => self::TRAVEL_TERM_MONTHS,
            ]);
        }

        if (LoanTypes::isRetireeShortTerm($loanType)
            && ($termMonths < self::RETIREE_SHORT_TERM_MIN_MONTHS || $termMonths > self::RETIREE_SHORT_TERM_MAX_MONTHS)) {
            return __('The retirees’ short-term loan term must be 12 to 24 months.');
        }

        if (LoanTypes::isEmergency($loanType) || LoanTypes::isCharacterEmergency($loanType)) {
            $type = LoanTypes::isCharacterEmergency($loanType)
                ? LoanTypes::CHARACTER_EMERGENCY
                : LoanTypes::EMERGENCY;

            $existing = CharacterLoan::query()
                ->forUser($member)
                ->whereRaw('UPPER(TRIM(loan_type)) = ?', [$type])
                ->whereIn('status', [LoanStatus::Pending, LoanStatus::Approved])
                ->count();

            if ($existing >= self::EMERGENCY_MAX_ORIGINALS_PLUS_RENEWALS) {
                return __('This loan may be renewed up to two (2) times.');
            }
        }

        return null;
    }
}
