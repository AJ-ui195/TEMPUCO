<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\Loan;
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
    public static function forLoan(Loan $loan): Collection
    {
        if ($loan->status !== LoanStatus::Approved) {
            return collect();
        }

        $principal = round((float) $loan->loan_amount, 2);
        $interest = round($principal * self::INTEREST_RATE, 2);
        $releaseDate = Carbon::parse($loan->loan_date ?? $loan->approved_at ?? $loan->created_at);

        $payments = $loan->relationLoaded('payments')
            ? $loan->payments->sortBy([
                fn (LoanPayment $payment) => $payment->received_at?->timestamp ?? 0,
                fn (LoanPayment $payment) => $payment->id,
            ])->values()
            : $loan->payments()->orderBy('received_at')->orderBy('id')->get();

        $totalPaid = round((float) $payments->sum(fn (LoanPayment $payment) => (float) $payment->amount), 2);
        $isPaid = $totalPaid + 0.005 >= $principal;

        $entries = collect();

        $entries->push([
            'date' => $releaseDate,
            'or' => '',
            'voucher' => '',
            'released' => $principal,
            'interest' => 0.0,
            'payment' => 0.0,
            'balance' => $principal,
            'surcharge_payment' => 0.0,
            'surcharge_balance' => 0.0,
            'remarks' => $isPaid ? '' : __('unpaid'),
        ]);

        $entries->push([
            'date' => $releaseDate,
            'or' => '',
            'voucher' => '',
            'released' => 0.0,
            'interest' => $interest,
            'payment' => 0.0,
            'balance' => $principal,
            'surcharge_payment' => 0.0,
            'surcharge_balance' => 0.0,
            'remarks' => '',
        ]);

        $balance = $principal;

        foreach ($payments as $payment) {
            $amount = round((float) $payment->amount, 2);
            $balance = round(max(0, $balance - $amount), 2);
            $clearsLoan = $balance < 0.005;

            $entries->push([
                'date' => Carbon::parse($payment->received_at),
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
     * @param  Collection<int, Loan>  $loans
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
}
