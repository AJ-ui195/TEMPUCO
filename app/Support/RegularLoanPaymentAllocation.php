<?php

namespace App\Support;

use App\Models\LoanPayment;
use App\Models\RegularLoan;
use Illuminate\Support\Carbon;

final class RegularLoanPaymentAllocation
{
    public const STATUS_PAID = 'paid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_UNPAID = 'unpaid';

    public const KIND_ADVANCE = 'advance';

    /**
     * @return array{
     *     periods: array<int, array{
     *         period: int,
     *         interest_due: float,
     *         principal_due: float,
     *         cash_flow_due: float,
     *         interest_allocated: float,
     *         principal_allocated: float,
     *         interest_remaining: float,
     *         principal_remaining: float,
     *         cash_flow_remaining: float,
     *         interest_status: string,
     *         principal_status: string,
     *         cash_flow_status: string
     *     }>,
     *     interest_allocated: float,
     *     principal_allocated: float,
     *     remaining_interest: float,
     *     remaining_principal: float
     * }
     */
    public static function allocate(RegularLoanSchedule $schedule, float $cash, int $dueCount = 1): array
    {
        $state = self::emptyPeriods($schedule);
        $state = self::applyCash($state, $cash, $dueCount);

        return self::finalize($schedule, $state);
    }

    /**
     * @return array{
     *     periods: array<int, array<string, mixed>>,
     *     interest_allocated: float,
     *     principal_allocated: float,
     *     remaining_interest: float,
     *     remaining_principal: float
     * }
     */
    public static function forLoan(RegularLoan $loan): array
    {
        $schedule = RegularLoanSchedule::fromLoan($loan);

        return self::finalize($schedule, self::replayState($loan, $schedule));
    }

    public static function cashPaid(RegularLoan $loan): float
    {
        $payments = $loan->relationLoaded('payments')
            ? $loan->payments
            : $loan->payments()->get();

        return round((float) $payments->sum(fn (LoanPayment $payment): float => (float) $payment->amount), 2);
    }

    public static function remainingCollectable(RegularLoan $loan): float
    {
        $allocated = self::forLoan($loan);

        return round($allocated['remaining_interest'] + $allocated['remaining_principal'], 2);
    }

    /**
     * @return array{interest: float, principal: float}
     */
    public static function splitAmount(RegularLoan $loan, float $amount): array
    {
        $amount = round($amount, 2);
        $schedule = RegularLoanSchedule::fromLoan($loan);
        $before = self::replayState($loan, $schedule);
        $dueCount = self::duePeriodCount($loan, $schedule, now());
        $after = self::applyCash($before, $amount, $dueCount);

        $interest = 0.0;
        $principal = 0.0;

        foreach ($after as $period => $entry) {
            $interest = round($interest + $entry['interest_allocated'] - $before[$period]['interest_allocated'], 2);
            $principal = round($principal + $entry['principal_allocated'] - $before[$period]['principal_allocated'], 2);
        }

        if ($interest < 0) {
            $interest = 0.0;
        }

        if ($interest > $amount) {
            $interest = $amount;
        }

        return [
            'interest' => $interest,
            'principal' => round($amount - $interest, 2),
        ];
    }

    public static function isAdvance(LoanPayment $payment): bool
    {
        return strcasecmp((string) ($payment->kind ?? ''), self::KIND_ADVANCE) === 0;
    }

    public static function advancePrincipalPaid(RegularLoan $loan): float
    {
        $payments = $loan->relationLoaded('payments')
            ? $loan->payments
            : $loan->payments()->get();

        return round((float) $payments
            ->filter(fn (LoanPayment $payment): bool => self::isAdvance($payment))
            ->sum(function (LoanPayment $payment): float {
                if ($payment->principal_applied !== null) {
                    return (float) $payment->principal_applied;
                }

                return (float) $payment->amount;
            }), 2);
    }

    public static function duePeriodCount(RegularLoan $loan, RegularLoanSchedule $schedule, mixed $asOf = null): int
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $start = $loan->first_payment_due_date
            ? Carbon::parse($loan->first_payment_due_date)->startOfDay()
            : ($loan->loan_date ? Carbon::parse($loan->loan_date)->startOfDay()->addMonth() : null);

        if ($start === null || $asOf->lt($start)) {
            return 1;
        }

        $months = (int) $start->startOfMonth()->diffInMonths($asOf->copy()->startOfMonth()) + 1;

        return max(1, min($schedule->termMonths, $months));
    }

    public static function status(float $allocated, float $due): string
    {
        if ($due <= RecordMemberLoanPayment::EPSILON) {
            return self::STATUS_PAID;
        }

        if ($allocated <= RecordMemberLoanPayment::EPSILON) {
            return self::STATUS_UNPAID;
        }

        if ($due - $allocated > RecordMemberLoanPayment::EPSILON) {
            return self::STATUS_PARTIAL;
        }

        return self::STATUS_PAID;
    }

    /**
     * @return array<int, array<string, float|int>>
     */
    private static function emptyPeriods(RegularLoanSchedule $schedule): array
    {
        $periods = [];

        foreach ($schedule->rows as $row) {
            $period = (int) ($row['period'] ?? 0);

            if ($period < 1) {
                continue;
            }

            $interestDue = round((float) ($row['interest'] ?? 0), 2);
            $principalDue = round((float) ($row['principal'] ?? 0), 2);
            $cashFlowDue = round((float) ($row['cash_flow'] ?? 0), 2);

            $periods[$period] = [
                'period' => $period,
                'interest_due' => $interestDue,
                'principal_due' => $principalDue,
                'cash_flow_due' => $cashFlowDue,
                'interest_allocated' => 0.0,
                'principal_allocated' => 0.0,
            ];
        }

        return $periods;
    }

    /**
     * @param  array<int, array<string, float|int>>  $periods
     * @return array<int, array<string, float|int>>
     */
    private static function applyCash(array $periods, float $cash, int $dueCount): array
    {
        if ($periods === []) {
            return $periods;
        }

        $lastPeriod = (int) max(array_keys($periods));
        $dueCount = max(1, min($lastPeriod, $dueCount));
        $remaining = round(max(0.0, $cash), 2);

        $remaining = self::fillInterest($periods, $remaining, 1, $dueCount);
        $remaining = self::fillPrincipal($periods, $remaining, 1, $dueCount);

        if ($remaining > RecordMemberLoanPayment::EPSILON && $dueCount < $lastPeriod) {
            foreach ($periods as $period => $entry) {
                if ($period <= $dueCount) {
                    continue;
                }

                $remaining = self::fillPrincipal($periods, $remaining, $period, $period);

                if ($remaining <= RecordMemberLoanPayment::EPSILON) {
                    break;
                }
            }
        }

        return $periods;
    }

    /**
     * @param  array<int, array<string, float|int>>  $periods
     */
    private static function fillInterest(array &$periods, float $remaining, int $from, int $to): float
    {
        foreach ($periods as $period => $entry) {
            if ($period < $from || $period > $to) {
                continue;
            }

            $need = round(max(0.0, $entry['interest_due'] - $entry['interest_allocated']), 2);
            $take = round(min($remaining, $need), 2);
            $periods[$period]['interest_allocated'] = round($entry['interest_allocated'] + $take, 2);
            $remaining = round($remaining - $take, 2);
        }

        return $remaining;
    }

    /**
     * @param  array<int, array<string, float|int>>  $periods
     */
    private static function fillPrincipal(array &$periods, float $remaining, int $from, int $to): float
    {
        foreach ($periods as $period => $entry) {
            if ($period < $from || $period > $to) {
                continue;
            }

            $need = round(max(0.0, $entry['principal_due'] - $entry['principal_allocated']), 2);
            $take = round(min($remaining, $need), 2);
            $periods[$period]['principal_allocated'] = round($entry['principal_allocated'] + $take, 2);
            $remaining = round($remaining - $take, 2);
        }

        return $remaining;
    }

    /**
     * @return array<int, array<string, float|int>>
     */
    private static function replayState(RegularLoan $loan, RegularLoanSchedule $schedule): array
    {
        $state = self::emptyPeriods($schedule);
        $payments = $loan->relationLoaded('payments')
            ? $loan->payments
            : $loan->payments()->get();

        foreach (LoanPayment::inRecordedOrder($payments) as $payment) {
            if (self::isAdvance($payment)) {
                continue;
            }

            $dueCount = self::duePeriodCount(
                $loan,
                $schedule,
                $payment->received_at ?? $payment->created_at,
            );
            $state = self::applyCash($state, (float) $payment->amount, $dueCount);
        }

        return $state;
    }

    public static function lastFullyPaidPeriod(RegularLoan $loan, RegularLoanSchedule $schedule): int
    {
        $payments = $loan->relationLoaded('payments')
            ? $loan->payments
            : $loan->payments()->get();

        $cash = 0.0;

        foreach (LoanPayment::inRecordedOrder($payments) as $payment) {
            if (self::isAdvance($payment)) {
                continue;
            }

            $cash = round($cash + (float) $payment->amount, 2);
        }

        $installment = round($schedule->monthlyInstallment, 2);

        if ($installment <= RecordMemberLoanPayment::EPSILON) {
            return 0;
        }

        $paid = (int) floor(($cash + RecordMemberLoanPayment::EPSILON) / $installment);

        return max(0, min($schedule->termMonths, $paid));
    }

    /**
     * @param  array<int, array<string, float|int>>  $state
     * @return array{
     *     periods: array<int, array<string, mixed>>,
     *     interest_allocated: float,
     *     principal_allocated: float,
     *     remaining_interest: float,
     *     remaining_principal: float
     * }
     */
    private static function finalize(RegularLoanSchedule $schedule, array $state): array
    {
        $interestAllocated = 0.0;
        $principalAllocated = 0.0;
        $periods = [];

        foreach ($state as $period => $entry) {
            $interestAllocated = round($interestAllocated + $entry['interest_allocated'], 2);
            $principalAllocated = round($principalAllocated + $entry['principal_allocated'], 2);
            $interestRemaining = round(max(0.0, $entry['interest_due'] - $entry['interest_allocated']), 2);
            $principalRemaining = round(max(0.0, $entry['principal_due'] - $entry['principal_allocated']), 2);
            $cashAllocated = round($entry['interest_allocated'] + $entry['principal_allocated'], 2);
            $cashRemaining = round(max(0.0, $entry['cash_flow_due'] - $cashAllocated), 2);

            $periods[$period] = [
                ...$entry,
                'interest_remaining' => $interestRemaining,
                'principal_remaining' => $principalRemaining,
                'cash_flow_remaining' => $cashRemaining,
                'interest_status' => self::status((float) $entry['interest_allocated'], (float) $entry['interest_due']),
                'principal_status' => self::status((float) $entry['principal_allocated'], (float) $entry['principal_due']),
                'cash_flow_status' => self::status($cashAllocated, (float) $entry['cash_flow_due']),
            ];
        }

        return [
            'periods' => $periods,
            'interest_allocated' => $interestAllocated,
            'principal_allocated' => $principalAllocated,
            'remaining_interest' => round(max(0.0, $schedule->totalInterest - $interestAllocated), 2),
            'remaining_principal' => round(max(0.0, $schedule->totalPrincipal - $principalAllocated), 2),
        ];
    }
}
