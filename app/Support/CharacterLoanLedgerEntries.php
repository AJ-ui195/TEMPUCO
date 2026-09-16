<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\Contracts\MemberLoan;
use App\Models\LoanPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Character / Character-Emergency style ledger:
 * release + prepaid interest, interest payments (balance unchanged), then principal payoff.
 * Regular members: 2%/mo. Retirees (`is_retiree`): 1%/mo.
 */
final class CharacterLoanLedgerEntries
{
    public const MONTHLY_INTEREST_RATE = 0.02;

    public const MONTHLY_INTEREST_RATE_RETIREE = 0.01;

    public const KIND_INTEREST = 'interest';

    public const KIND_PRINCIPAL = 'principal';

    /**
     * Recurring interest for one repayment period (Character-Emergency: rate/mo × term, typically 3).
     */
    public static function periodInterest(MemberLoan $loan): float
    {
        $principal = round((float) $loan->loan_amount, 2);
        $stored = round((float) $loan->installment_amount, 2);

        if ($stored > 0.005 && $stored + 0.005 < $principal) {
            return $stored;
        }

        $months = max(1, (int) $loan->loan_period_months);
        $periodMonths = $months <= 3 ? $months : ($months >= 12 ? 1 : $months);
        $rate = $loan->user?->isRetiree()
            ? self::MONTHLY_INTEREST_RATE_RETIREE
            : self::MONTHLY_INTEREST_RATE;

        return round($principal * $rate * $periodMonths, 2);
    }

    public static function repaymentIntervalMonths(MemberLoan $loan): int
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
    public static function forLoan(MemberLoan $loan): Collection
    {
        if ($loan->status !== LoanStatus::Approved) {
            return collect();
        }

        $principal = round((float) $loan->loan_amount, 2);
        $periodInterest = self::periodInterest($loan);
        $releaseDate = Carbon::parse($loan->loan_date ?? $loan->approved_at ?? $loan->created_at);

        $payments = LoanPayment::inRecordedOrder(
            $loan->relationLoaded('payments')
                ? $loan->payments
                : $loan->payments()->get()
        );

        $remarksIntervalMonths = 3;
        $dueCursor = $loan->first_payment_due_date
            ? Carbon::parse($loan->first_payment_due_date)
            : $releaseDate->copy()->addMonthsNoOverflow($remarksIntervalMonths);

        $entries = collect();

        $entries->push([
            'date' => $releaseDate,
            'stored_at' => $loan->created_at ?? $releaseDate,
            'stored_id' => 0,
            'or' => '',
            'voucher' => '',
            'released' => $principal,
            'interest' => $periodInterest,
            'interest_in_parens' => true,
            'payment' => 0.0,
            'balance' => $principal,
            'balance_blank' => false,
            'surcharge' => 0.0,
            'remarks' => $dueCursor->format('n-j-Y'),
        ]);

        $balance = $principal;

        foreach ($payments as $payment) {
            $amount = round((float) $payment->amount, 2);
            $date = $payment->created_at ?? Carbon::parse($payment->received_at);
            $or = (string) ($payment->official_receipt_no ?? '');

            if (self::isInterestPayment($payment)) {
                $dueCursor = $dueCursor->copy()->addMonthsNoOverflow($remarksIntervalMonths);

                $entries->push([
                    'date' => $date,
                    'stored_at' => $date,
                    'stored_id' => (int) $payment->id,
                    'or' => $or,
                    'voucher' => '',
                    'released' => 0.0,
                    'interest' => $amount > 0.005 ? $amount : $periodInterest,
                    'interest_in_parens' => false,
                    'payment' => 0.0,
                    'balance' => $balance,
                    'balance_blank' => false,
                    'surcharge' => 0.0,
                    'remarks' => $dueCursor->format('n-j-Y'),
                ]);

                continue;
            }

            $balance = round(max(0, $balance - $amount), 2);
            $clears = $balance < 0.005;

            $entries->push([
                'date' => $date,
                'stored_at' => $date,
                'stored_id' => (int) $payment->id,
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
     * @param  Collection<int, MemberLoan>  $loans
     * @return Collection<int, array<string, mixed>>
     */
    public static function forLoans(Collection $loans): Collection
    {
        return LedgerChronology::sortByStoredTime(
            $loans,
            fn (MemberLoan $loan) => $loan->created_at ?? $loan->loan_date,
            fn (MemberLoan $loan): int => (int) $loan->id,
        )->flatMap(fn (MemberLoan $loan): Collection => self::forLoan($loan))->values();
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
