<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\CharacterLoan;
use App\Models\Contracts\MemberLoan;
use App\Models\InvoiceFeePayment;
use App\Models\Member;
use App\Models\QuickLoan;

final class InvoiceInterestSettlement
{
    public static function pool(int $memberId): float
    {
        return round((float) InvoiceFeePayment::query()
            ->where('member_id', $memberId)
            ->where('settles_loan_interest', true)
            ->sum('interest'), 2);
    }

    public static function dueInterest(Member $member): float
    {
        $needed = 0.0;

        foreach (self::obligations($member) as $obligation) {
            $needed = round($needed + $obligation['amount'], 2);
        }

        return $needed;
    }

    public static function outstandingFor(Member $member): float
    {
        $remaining = 0.0;

        foreach (self::remaindersForMemberId((int) $member->id) as $row) {
            $remaining = round($remaining + $row['remaining'], 2);
        }

        return $remaining;
    }

    public static function covers(MemberLoan $loan): bool
    {
        foreach (self::remaindersForMemberId((int) $loan->member_id) as $row) {
            if ($row['loan_id'] === (int) $loan->getKey() && $row['type'] === $loan::class) {
                return $row['amount'] > 0.005 && $row['remaining'] < 0.01;
            }
        }

        return false;
    }

    /**
     * @return list<array{invoice_no: string, amount: float, received_at: mixed, id: int}>
     */
    public static function allocationsFor(MemberLoan $loan): array
    {
        $hits = [];

        foreach (self::remaindersForMemberId((int) $loan->member_id) as $row) {
            if ($row['loan_id'] !== (int) $loan->getKey() || $row['type'] !== $loan::class) {
                continue;
            }

            foreach ($row['hits'] as $hit) {
                $hits[] = $hit;
            }
        }

        return $hits;
    }

    /**
     * @return list<array{type: class-string, loan_id: int, amount: float}>
     */
    public static function obligations(Member $member): array
    {
        return self::obligationsForMemberId((int) $member->id);
    }

    /**
     * @return list<array{
     *     type: class-string,
     *     loan_id: int,
     *     amount: float,
     *     remaining: float,
     *     hits: list<array{invoice_no: string, amount: float, received_at: mixed, id: int}>
     * }>
     */
    private static function remaindersForMemberId(int $memberId): array
    {
        $invoices = InvoiceFeePayment::query()
            ->where('member_id', $memberId)
            ->where('settles_loan_interest', true)
            ->where('interest', '>', 0)
            ->orderBy('id')
            ->get();

        $chunks = $invoices->map(fn (InvoiceFeePayment $invoice): array => [
            'invoice' => $invoice,
            'remaining' => round((float) $invoice->interest, 2),
        ])->all();

        $rows = [];

        foreach (self::obligationsForMemberId($memberId, includeSettled: true) as $obligation) {
            $need = $obligation['amount'];
            $hits = [];

            foreach ($chunks as &$chunk) {
                if ($need < 0.01) {
                    break;
                }

                $take = round(min($need, $chunk['remaining']), 2);

                if ($take < 0.01) {
                    continue;
                }

                $invoice = $chunk['invoice'];
                $hits[] = [
                    'invoice_no' => (string) $invoice->invoice_no,
                    'amount' => $take,
                    'received_at' => $invoice->received_at ?? $invoice->created_at,
                    'id' => (int) $invoice->id,
                ];

                $chunk['remaining'] = round($chunk['remaining'] - $take, 2);
                $need = round($need - $take, 2);
            }
            unset($chunk);

            $rows[] = [
                'type' => $obligation['type'],
                'loan_id' => $obligation['loan_id'],
                'amount' => $obligation['amount'],
                'remaining' => $need,
                'hits' => $hits,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{type: class-string, loan_id: int, amount: float}>
     */
    private static function obligationsForMemberId(int $memberId, bool $includeSettled = false): array
    {
        $items = [];

        $characters = CharacterLoan::query()
            ->where('member_id', $memberId)
            ->where('status', LoanStatus::Approved)
            ->with('payments')
            ->orderBy('id')
            ->get();

        foreach ($characters as $loan) {
            if (! $includeSettled && RecordMemberLoanPayment::remainingPrincipal($loan) < 0.01) {
                continue;
            }

            $short = max(0, CharacterLoanLedgerEntries::interestPeriodsDue($loan)
                - CharacterLoanLedgerEntries::interestPaymentCount($loan));

            if ($short < 1) {
                continue;
            }

            $items[] = [
                'type' => CharacterLoan::class,
                'loan_id' => (int) $loan->id,
                'amount' => round(CharacterLoanLedgerEntries::periodInterest($loan) * $short, 2),
            ];
        }

        $quicks = QuickLoan::query()
            ->where('member_id', $memberId)
            ->where('status', LoanStatus::Approved)
            ->orderBy('id')
            ->get();

        foreach ($quicks as $loan) {
            if (! $includeSettled && RecordMemberLoanPayment::remainingPrincipal($loan) < 0.01) {
                continue;
            }

            $items[] = [
                'type' => QuickLoan::class,
                'loan_id' => (int) $loan->id,
                'amount' => QuickLoanLedgerEntries::interestOn((float) $loan->loan_amount),
            ];
        }

        usort($items, function (array $left, array $right): int {
            $byAmount = $left['amount'] <=> $right['amount'];

            return $byAmount !== 0 ? $byAmount : ($left['loan_id'] <=> $right['loan_id']);
        });

        return $items;
    }
}
