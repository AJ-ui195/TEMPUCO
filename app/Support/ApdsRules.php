<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\Member;
use App\Models\RegularLoan;
use Carbon\Carbon;

/**
 * APDS policy rules (Sections 2–5) for regular / salary loans.
 */
final class ApdsRules
{
    public const SERVICE_CHARGE_FIRST_1_TO_5_YEARS = 0.0525;

    public const SERVICE_CHARGE_FIRST_6_TO_7_YEARS = 0.06;

    public const SERVICE_CHARGE_SECOND_ACCOUNT = 0.06;

    public const CAPITAL_BUILD_UP_RETENTION = 5000.0;

    public const FIRST_APDS_CAPITAL_BUILD_UP = 10000.0;

    public const FIRST_APDS_MAX_AMOUNT = 300000.0;

    public const FIRST_APDS_MIN_TERM_MONTHS = 12;

    public const FIRST_APDS_MAX_TERM_MONTHS = 84;

    public const APDS_MAX_TERM_MONTHS = 84;

    public const RESTRUCTURE_AGGREGATE_MAX = 750000.0;

    public static function isSecondAccountType(string $loanType): bool
    {
        return LoanTypes::isSalary2($loanType);
    }

    /**
     * Section 2 — one-time service charge rate.
     * First APDS (Salary loan 1): 5.25% for 1–5 years, 6.00% for 6–7 years.
     * Second APDS (Salary loan 2): 6.00% for 1–7 years.
     */
    public static function serviceChargeRate(bool $isSecondApdsAccount, int $termMonths): float
    {
        if ($isSecondApdsAccount) {
            return self::SERVICE_CHARGE_SECOND_ACCOUNT;
        }

        $years = max(1, intdiv(max(1, $termMonths), 12));

        return $years >= 6
            ? self::SERVICE_CHARGE_FIRST_6_TO_7_YEARS
            : self::SERVICE_CHARGE_FIRST_1_TO_5_YEARS;
    }

    /**
     * Fee line items that sum to the Section 2 service-charge rate.
     *
     * @return list<array{key: string, rate: float}>
     */
    public static function otherChargeLines(float $serviceChargeRate): array
    {
        $fixed = 0.01 + 0.01 + 0.01 + 0.01; // insurance, processing, notarial, verification
        $serviceFee = round(max(0.0, $serviceChargeRate - $fixed), 4);

        return [
            ['key' => 'credit_loan_insurance', 'rate' => 0.01],
            ['key' => 'processing_fee', 'rate' => 0.01],
            ['key' => 'service_fee', 'rate' => $serviceFee],
            ['key' => 'notarial_fee', 'rate' => 0.01],
            ['key' => 'verification_fee', 'rate' => 0.01],
        ];
    }

    /**
     * Section 3 — max age at application for extended terms. Null = no age cap.
     */
    public static function maxAgeForTermYears(int $termYears): ?int
    {
        return match ($termYears) {
            4 => 56,
            5 => 55,
            6 => 54,
            7 => 53,
            default => null,
        };
    }

    public static function ageAt(?\DateTimeInterface $dateOfBirth): ?int
    {
        return $dateOfBirth === null ? null : Carbon::instance($dateOfBirth)->age;
    }

    /**
     * @return string|null Validation error message, or null if OK / skipped.
     */
    public static function ageRequirementError(?\DateTimeInterface $dateOfBirth, int $termMonths): ?string
    {
        if ($dateOfBirth === null) {
            return null; // DOB optional — skip Section 3 when blank
        }

        $termYears = max(1, intdiv(max(1, $termMonths), 12));
        $maxAge = self::maxAgeForTermYears($termYears);

        if ($maxAge === null) {
            return null;
        }

        $age = Carbon::instance($dateOfBirth)->age;

        if ($age > $maxAge) {
            return __('For a :years-year term, the borrower must not be more than :max years old at application. Current age: :age.', [
                'years' => $termYears,
                'max' => $maxAge,
                'age' => $age,
            ]);
        }

        return null;
    }

    public static function isSecondApdsAccount(Member $user, ?int $excludeLoanId = null): bool
    {
        $query = RegularLoan::query()
            ->forUser($user)
            ->where('status', LoanStatus::Approved)
            ->whereNotIn('loan_type', LoanTypes::excludedFromApdsAccounts());

        if ($excludeLoanId !== null) {
            $query->whereKeyNot($excludeLoanId);
        }

        return $query->exists();
    }

    public static function approvedApdsAmount(Member $user): float
    {
        return round((float) RegularLoan::query()
            ->forUser($user)
            ->where('status', LoanStatus::Approved)
            ->whereNotIn('loan_type', LoanTypes::excludedFromApdsAccounts())
            ->sum('loan_amount'), 2);
    }

    public static function hasQualifyingApdsForCharacter(Member $user): bool
    {
        return self::approvedApdsAmount($user) + 0.005 >= self::FIRST_APDS_MAX_AMOUNT;
    }

    public static function capitalBuildUpRetention(bool $isSecondApdsAccount): float
    {
        return $isSecondApdsAccount
            ? self::CAPITAL_BUILD_UP_RETENTION
            : self::FIRST_APDS_CAPITAL_BUILD_UP;
    }

    /**
     * First APDS for new members: loanable amount up to ₱300,000.
     *
     * @return string|null Validation error message, or null if OK / not first APDS.
     */
    public static function firstApdsAmountError(Member $user, float $amount, string $loanType = LoanTypes::REGULAR): ?string
    {
        if (self::isSecondAccountType($loanType)) {
            return null;
        }

        if ($amount > self::FIRST_APDS_MAX_AMOUNT) {
            return __('Qualified new members may avail up to ₱:max on the first APDS regular loan.', [
                'max' => number_format(self::FIRST_APDS_MAX_AMOUNT, 2),
            ]);
        }

        return null;
    }

    /**
     * Salary loan 1 (first APDS) and Salary loan 2 (second APDS): 1–7 years.
     *
     * @return string|null Validation error message, or null if OK.
     */
    public static function firstApdsTermError(Member $user, int $termMonths, string $loanType = LoanTypes::REGULAR): ?string
    {
        if ($termMonths < self::FIRST_APDS_MIN_TERM_MONTHS || $termMonths > self::APDS_MAX_TERM_MONTHS) {
            return self::isSecondAccountType($loanType)
                ? __('The second APDS (Salary loan 2) term must be one (1) to seven (7) years (:min–:max months).', [
                    'min' => self::FIRST_APDS_MIN_TERM_MONTHS,
                    'max' => self::APDS_MAX_TERM_MONTHS,
                ])
                : __('The first APDS (Salary loan 1) term must be one (1) to seven (7) years (:min–:max months).', [
                    'min' => self::FIRST_APDS_MIN_TERM_MONTHS,
                    'max' => self::APDS_MAX_TERM_MONTHS,
                ]);
        }

        return null;
    }

    /**
     * Section 4 — restructured aggregate must not exceed ₱750,000.
     *
     * @return string|null Validation error message, or null if OK.
     */
    public static function restructureAggregateError(
        Member $user,
        float $newLoanAmount,
        bool $isRestructure,
        ?int $excludeLoanId = null,
    ): ?string {
        if (! $isRestructure) {
            return null;
        }

        $existing = (float) RegularLoan::query()
            ->forUser($user)
            ->where('status', LoanStatus::Approved)
            ->whereNotIn('loan_type', LoanTypes::excludedFromApdsAccounts())
            ->when($excludeLoanId !== null, fn ($q) => $q->whereKeyNot($excludeLoanId))
            ->sum('loan_amount');

        $aggregate = round($existing + $newLoanAmount, 2);

        if ($aggregate > self::RESTRUCTURE_AGGREGATE_MAX) {
            return __('For restructured accounts, the aggregate amount of the first and second APDS accounts must not exceed ₱:max. Proposed aggregate: ₱:aggregate.', [
                'max' => number_format(self::RESTRUCTURE_AGGREGATE_MAX, 2),
                'aggregate' => number_format($aggregate, 2),
            ]);
        }

        return null;
    }
}
