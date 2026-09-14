<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\Contracts\MemberLoan;
use App\Models\LoanPayment;
use App\Models\QuickLoan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class RecordMemberLoanPayment
{
    public const EPSILON = 0.005;

    public static function kindOf(MemberLoan $loan): string
    {
        if ($loan instanceof QuickLoan) {
            return LoanTypes::QUICK;
        }

        return strtoupper(trim((string) $loan->loan_type));
    }

    public static function isCharacterFamily(MemberLoan $loan): bool
    {
        return LoanTypes::isCharacterFamily(self::kindOf($loan));
    }

    public static function requiresOfficialReceipt(MemberLoan $loan): bool
    {
        $type = self::kindOf($loan);

        return $type === LoanTypes::QUICK || in_array($type, LoanTypes::characterFamily(), true);
    }

    public static function remainingPrincipal(MemberLoan $loan): float
    {
        if ($loan->status !== LoanStatus::Approved) {
            return 0.0;
        }

        $principal = round((float) $loan->loan_amount, 2);
        $payments = $loan->relationLoaded('payments')
            ? $loan->payments
            : $loan->payments()->get();

        if ($loan instanceof QuickLoan) {
            $due = QuickLoanLedgerEntries::totalPayable($principal);
            $paid = round((float) $payments->sum(fn (LoanPayment $payment): float => (float) $payment->amount), 2);

            return round(max(0, $due - $paid), 2);
        }

        $paid = round((float) $payments
            ->filter(fn (LoanPayment $payment): bool => CharacterLoanLedgerEntries::isPrincipalPayment($payment))
            ->sum(fn (LoanPayment $payment): float => (float) $payment->amount), 2);

        return round(max(0, $principal - $paid), 2);
    }

    /**
     * @return array{kind: string, amount: float, official_receipt_no: ?string, received_at: mixed}
     */
    public static function defaults(MemberLoan $loan): array
    {
        $type = self::kindOf($loan);
        $kind = CharacterLoanLedgerEntries::KIND_PRINCIPAL;
        $amount = self::remainingPrincipal($loan);

        if (in_array($type, LoanTypes::characterFamily(), true)) {
            $kind = CharacterLoanLedgerEntries::KIND_INTEREST;
            $amount = CharacterLoanLedgerEntries::periodInterest($loan);
        }

        return [
            'kind' => $kind,
            'amount' => $amount > self::EPSILON ? $amount : 0.0,
            'official_receipt_no' => null,
            'received_at' => now(),
        ];
    }

    public static function apply(
        MemberLoan $loan,
        float $amount,
        string $kind,
        ?string $officialReceiptNo = null,
        mixed $receivedAt = null,
    ): LoanPayment {
        if ($loan->status !== LoanStatus::Approved) {
            throw new InvalidArgumentException(__('Only approved loans can receive payments.'));
        }

        $amount = round($amount, 2);

        if ($amount < 0.01) {
            throw new InvalidArgumentException(__('Enter a payment amount.'));
        }

        $type = self::kindOf($loan);
        $kind = strtolower(trim($kind)) ?: CharacterLoanLedgerEntries::KIND_PRINCIPAL;

        if (in_array($type, LoanTypes::characterFamily(), true)
            && ! in_array($kind, [CharacterLoanLedgerEntries::KIND_INTEREST, CharacterLoanLedgerEntries::KIND_PRINCIPAL], true)) {
            throw new InvalidArgumentException(__('Choose interest or principal.'));
        }

        if ($kind !== CharacterLoanLedgerEntries::KIND_INTEREST) {
            $remaining = self::remainingPrincipal($loan);

            if ($amount - $remaining > self::EPSILON) {
                throw new InvalidArgumentException(__('Payment cannot be more than the ₱:amount remaining principal.', [
                    'amount' => number_format($remaining, 2),
                ]));
            }
        }

        if (self::requiresOfficialReceipt($loan) && blank($officialReceiptNo)) {
            throw new InvalidArgumentException(__('O.R. # is required for this loan.'));
        }

        return DB::transaction(function () use ($loan, $amount, $kind, $officialReceiptNo, $receivedAt, $type): LoanPayment {
            return $loan->payments()->create([
                'amount' => $amount,
                'kind' => in_array($type, LoanTypes::characterFamily(), true)
                    ? $kind
                    : CharacterLoanLedgerEntries::KIND_PRINCIPAL,
                'official_receipt_no' => $officialReceiptNo,
                'received_at' => $receivedAt ?? now(),
            ]);
        });
    }
}
