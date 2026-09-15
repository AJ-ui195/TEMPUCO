<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\QuickLoan;
use App\Models\LoanPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds Quick Loan individual-ledger rows from an approved loan + payments.
 * No separate ledger table — entries are derived.
 */
final class QuickLoanLedgerEntries
{
    public const INTEREST_RATE = 0.01;

    public const MAX_AMOUNT = 2000.0;

    public static function totalPayable(float $amount): float
    {
        return round($amount + ($amount * self::INTEREST_RATE), 2);
    }

    /**
     * @return Collection<int, array{
     *     date: Carbon,
     *     or: string,
     *     voucher: string,
     *     released: float,
     *     interest: float,
     *     payment: float,
     *     balance: float,
     *     surcharge_payment: float,
     *     surcharge_balance: float,
     *     remarks: string
     * }>
     */
    public static function forLoan(QuickLoan $loan): Collection
    {
        if ($loan->status !== LoanStatus::Approved) {
            return collect();
        }

        $principal = round((float) $loan->loan_amount, 2);
        $interest = round($principal * self::INTEREST_RATE, 2);
        $releaseDate = Carbon::parse($loan->loan_date ?? $loan->approved_at ?? $loan->created_at);

        $payments = LoanPayment::inRecordedOrder(
            $loan->relationLoaded('payments')
                ? $loan->payments
                : $loan->payments()->get()
        );

        $due = self::totalPayable($principal);
        $firstDue = $loan->first_payment_due_date
            ? Carbon::parse($loan->first_payment_due_date)->format('n-j-Y')
            : $releaseDate->copy()->addMonthNoOverflow()->format('n-j-Y');

        $entries = collect();

        $entries->push([
            'date' => $releaseDate,
            'stored_at' => $loan->created_at ?? $releaseDate,
            'stored_id' => 0,
            'or' => '',
            'voucher' => '',
            'released' => $principal,
            'interest' => $interest,
            'payment' => 0.0,
            'balance' => $due,
            'surcharge_payment' => 0.0,
            'surcharge_balance' => 0.0,
            'remarks' => $firstDue,
        ]);

        $balance = $due;

        foreach ($payments as $payment) {
            $amount = round((float) $payment->amount, 2);
            $balance = round(max(0, $balance - $amount), 2);
            $clearsLoan = $balance < 0.005;

            $storedAt = $payment->created_at ?? Carbon::parse($payment->received_at);

            $entries->push([
                'date' => $storedAt,
                'stored_at' => $storedAt,
                'stored_id' => (int) $payment->id,
                'or' => (string) ($payment->official_receipt_no ?? ''),
                'voucher' => '',
                'released' => 0.0,
                'interest' => 0.0,
                'payment' => $amount,
                'balance' => $balance,
                'surcharge_payment' => 0.0,
                'surcharge_balance' => 0.0,
                'remarks' => $clearsLoan ? __('paid') : '',
            ]);
        }

        return $entries;
    }

    /**
     * @param  Collection<int, QuickLoan>  $loans
     * @return Collection<int, array{
     *     date: Carbon,
     *     or: string,
     *     voucher: string,
     *     released: float,
     *     interest: float,
     *     payment: float,
     *     balance: float,
     *     surcharge_payment: float,
     *     surcharge_balance: float,
     *     remarks: string
     * }>
     */
    public static function forLoans(Collection $loans): Collection
    {
        return LedgerChronology::sortByStoredTime(
            $loans,
            fn (QuickLoan $loan) => $loan->created_at ?? $loan->loan_date,
            fn (QuickLoan $loan): int => (int) $loan->id,
        )->flatMap(fn (QuickLoan $loan): Collection => self::forLoan($loan))->values();
    }
}
