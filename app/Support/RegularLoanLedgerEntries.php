<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\LoanPayment;
use App\Models\RegularLoan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class RegularLoanLedgerEntries
{
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
    public static function forLoan(?RegularLoan $loan): Collection
    {
        if ($loan === null || $loan->status !== LoanStatus::Approved) {
            return collect();
        }

        $principal = round((float) $loan->loan_amount, 2);
        $releaseDate = Carbon::parse($loan->loan_date ?? $loan->approved_at ?? $loan->created_at);
        $schedule = RegularLoanSchedule::fromLoan($loan);
        $firstInterest = (float) ($schedule->rows[1]['interest'] ?? 0);

        $payments = LoanPayment::inRecordedOrder(
            $loan->relationLoaded('payments')
                ? $loan->payments
                : $loan->payments()->get()
        );

        $entries = collect();
        $entries->push([
            'date' => $releaseDate,
            'or' => '',
            'voucher' => '',
            'released' => $principal,
            'interest' => $firstInterest,
            'interest_in_parens' => $firstInterest > 0.005,
            'payment' => 0.0,
            'balance' => $principal,
            'balance_blank' => false,
            'surcharge' => 0.0,
            'remarks' => $releaseDate->format('n-Y'),
        ]);

        $balance = $principal;

        foreach ($payments as $payment) {
            $amount = round((float) $payment->amount, 2);
            $interest = round((float) ($payment->interest_applied ?? 0), 2);
            $principalApplied = round((float) ($payment->principal_applied ?? 0), 2);
            $date = $payment->received_at
                ? Carbon::parse($payment->received_at)
                : ($payment->created_at ?? $releaseDate);
            $or = CollectionReceiptNumbers::displayOfficialReceipt((string) ($payment->official_receipt_no ?? ''));

            if ($principalApplied < 0.005 && $interest < 0.005) {
                $principalApplied = $amount;
            }

            if ($principalApplied > 0.005) {
                $balance = round(max(0, $balance - $principalApplied), 2);
            }

            $clears = $balance < 0.005;

            $entries->push([
                'date' => $date,
                'or' => $or,
                'voucher' => '',
                'released' => 0.0,
                'interest' => $interest,
                'interest_in_parens' => false,
                'payment' => $principalApplied > 0.005 ? $principalApplied : ($interest > 0.005 ? 0.0 : $amount),
                'balance' => $clears ? 0.0 : $balance,
                'balance_blank' => $clears,
                'surcharge' => 0.0,
                'remarks' => $clears ? __('paid') : ($or === '' ? __('Deduct from payroll') : ''),
            ]);
        }

        return $entries;
    }
}
