<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Character / Character-Emergency style ledger:
 * release + prepaid interest, interest payments (balance unchanged), then principal payoff.
 */
final class CharacterLoanLedgerEntries
{
    public const MONTHLY_INTEREST_RATE = 0.02;

    public const KIND_INTEREST = 'interest';

    public const KIND_PRINCIPAL = 'principal';

    /**
     * Recurring interest for one repayment period (Character-Emergency: 2%/mo × term, typically 3 → 6%).
     */
    public static function periodInterest(Loan $loan): float
    {
        $principal = round((float) $loan->loan_amount, 2);
        $stored = round((float) $loan->installment_amount, 2);

        if ($stored > 0.005 && $stored + 0.005 < $principal) {
            return $stored;
        }

        $months = max(1, (int) $loan->loan_period_months);
        $periodMonths = $months <= 3 ? $months : ($months >= 12 ? 1 : $months);

        return round($principal * self::MONTHLY_INTEREST_RATE * $periodMonths, 2);
    }

    public static function repaymentIntervalMonths(Loan $loan): int
    {
        $months = max(1, (int) $loan->loan_period_months);

        return $months <= 3 ? $months : ($months >= 12 ? 1 : 3);
    }

    /**
     * @return Collection<int, array{
     *     date: Carbon,
     *     or: string,
     *     voucher: string,
     *     released: float,
     *     interest: float,
     *     interest_in_parens: bool,
     *     payment: float,
     *     balance: float,
     *     balance_blank: bool,
     *     surcharge: float,
     *     remarks: string
     * }>
     */
    public static function forLoan(Loan $loan): Collection
    {
        if ($loan->status !== LoanStatus::Approved) {
            return collect();
        }

        $principal = round((float) $loan->loan_amount, 2);
        $periodInterest = self::periodInterest($loan);
        $intervalMonths = self::repaymentIntervalMonths($loan);
        $releaseDate = Carbon::parse($loan->loan_date ?? $loan->approved_at ?? $loan->created_at);

        $payments = $loan->relationLoaded('payments')
            ? $loan->payments->sortBy([
                fn (LoanPayment $payment) => $payment->received_at?->timestamp ?? 0,
                fn (LoanPayment $payment) => $payment->id,
            ])->values()
            : $loan->payments()->orderBy('received_at')->orderBy('id')->get();

        $principalPaid = round((float) $payments
            ->filter(fn (LoanPayment $payment): bool => self::isPrincipalPayment($payment))
            ->sum(fn (LoanPayment $payment) => (float) $payment->amount), 2);

        $isPaid = $principalPaid + 0.005 >= $principal;
        $nextDue = $loan->first_payment_due_date
            ? Carbon::parse($loan->first_payment_due_date)
            : $releaseDate->copy()->addMonthsNoOverflow($intervalMonths);

        $entries = collect();

        $entries->push([
            'date' => $releaseDate,
            'or' => '',
            'voucher' => '',
            'released' => $principal,
            'interest' => $periodInterest,
            'interest_in_parens' => true,
            'payment' => 0.0,
            'balance' => $principal,
            'balance_blank' => false,
            'surcharge' => 0.0,
            'remarks' => $isPaid ? '' : $nextDue->format('n-j-Y'),
        ]);

        $balance = $principal;
        $dueCursor = $nextDue->copy();

        foreach ($payments as $payment) {
            $amount = round((float) $payment->amount, 2);
            $date = Carbon::parse($payment->received_at);
            $or = (string) ($payment->official_receipt_no ?? '');

            if (self::isInterestPayment($payment)) {
                $dueCursor = $date->copy()->addMonthsNoOverflow($intervalMonths);

                $entries->push([
                    'date' => $date,
                    'or' => $or,
                    'voucher' => '',
                    'released' => 0.0,
                    'interest' => $amount > 0.005 ? $amount : $periodInterest,
                    'interest_in_parens' => false,
                    'payment' => 0.0,
                    'balance' => $balance,
                    'balance_blank' => false,
                    'surcharge' => 0.0,
                    'remarks' => $isPaid ? '' : $dueCursor->format('n-j-Y'),
                ]);

                continue;
            }

            $balance = round(max(0, $balance - $amount), 2);
            $clears = $balance < 0.005;

            $entries->push([
                'date' => $date,
                'or' => $or,
                'voucher' => '',
                'released' => 0.0,
                'interest' => 0.0,
                'interest_in_parens' => false,
                'payment' => $amount,
                'balance' => $clears ? 0.0 : $balance,
                'balance_blank' => $clears,
                'surcharge' => 0.0,
                'remarks' => $clears ? __('paid') : '',
            ]);
        }

        return $entries;
    }

    /**
     * @param  Collection<int, Loan>  $loans
     * @return Collection<int, array<string, mixed>>
     */
    public static function forLoans(Collection $loans): Collection
    {
        return $loans
            ->sortBy([
                fn (Loan $loan) => $loan->loan_date?->timestamp
                    ?? $loan->approved_at?->timestamp
                    ?? $loan->created_at?->timestamp
                    ?? 0,
                fn (Loan $loan) => $loan->id,
            ])
            ->flatMap(fn (Loan $loan): Collection => self::forLoan($loan))
            ->values();
    }

    public static function isInterestPayment(LoanPayment $payment): bool
    {
        return strcasecmp((string) ($payment->kind ?? self::KIND_PRINCIPAL), self::KIND_INTEREST) === 0;
    }

    public static function isPrincipalPayment(LoanPayment $payment): bool
    {
        return ! self::isInterestPayment($payment);
    }
}
