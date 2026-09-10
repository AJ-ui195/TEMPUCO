<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * APDS policy rules (Sections 2–5) for regular / salary loans.
 */
final class ApdsRules
{
    public const SERVICE_CHARGE_FIRST_1_TO_5_YEARS = 0.0525;

    public const SERVICE_CHARGE_FIRST_6_TO_7_YEARS = 0.06;

    public const SERVICE_CHARGE_SECOND_ACCOUNT = 0.06;

    public const CAPITAL_BUILD_UP_RETENTION = 5000.0;

    public const RESTRUCTURE_AGGREGATE_MAX = 750000.0;

    /**
     * Section 2 — one-time service charge rate.
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

    public static function ageAt(?Carbon $dateOfBirth): ?int
    {
        return $dateOfBirth?->age;
    }

    /**
     * @return string|null Validation error message, or null if OK / skipped.
     */
    public static function ageRequirementError(?Carbon $dateOfBirth, int $termMonths): ?string
    {
        if ($dateOfBirth === null) {
            return null; // DOB optional — skip Section 3 when blank
        }

        $termYears = max(1, intdiv(max(1, $termMonths), 12));
        $maxAge = self::maxAgeForTermYears($termYears);

        if ($maxAge === null) {
            return null;
        }

        $age = $dateOfBirth->age;

        if ($age > $maxAge) {
            return __('For a :years-year term, the borrower must not be more than :max years old at application. Current age: :age.', [
                'years' => $termYears,
                'max' => $maxAge,
                'age' => $age,
            ]);
        }

        return null;
    }

    public static function isSecondApdsAccount(User $user, ?int $excludeLoanId = null): bool
    {
        $query = Loan::query()
            ->forUser($user)
            ->where('status', LoanStatus::Approved)
            ->whereNotIn('loan_type', LoanTypes::nonRegular());

        if ($excludeLoanId !== null) {
            $query->whereKeyNot($excludeLoanId);
        }

        return $query->exists();
    }

    public static function capitalBuildUpRetention(bool $isSecondApdsAccount): float
    {
        return $isSecondApdsAccount ? self::CAPITAL_BUILD_UP_RETENTION : 0.0;
    }

    /**
     * Section 4 — restructured aggregate must not exceed ₱750,000.
     *
     * @return string|null Validation error message, or null if OK.
     */
    public static function restructureAggregateError(
        User $user,
        float $newLoanAmount,
        bool $isRestructure,
        ?int $excludeLoanId = null,
    ): ?string {
        if (! $isRestructure) {
            return null;
        }

        $existing = (float) Loan::query()
            ->forUser($user)
            ->where('status', LoanStatus::Approved)
            ->whereNotIn('loan_type', LoanTypes::nonRegular())
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
