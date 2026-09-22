<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\Contracts\MemberLoan;
use App\Models\LoanPayment;
use App\Models\QuickLoan;
use App\Models\RegularLoan;
use App\Models\User;
use App\Support\RoleDashboard;
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
            $paid = round((float) $payments->sum(fn (LoanPayment $payment): float => (float) $payment->amount), 2);

            return round(max(0, $principal - $paid), 2);
        }

        if ($loan instanceof RegularLoan) {
            $paid = round((float) $payments->sum(
                fn (LoanPayment $payment): float => self::regularPrincipalApplied($payment)
            ), 2);

            return round(max(0, $principal - $paid), 2);
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
        ?string $receiptKind = null,
        ?int $receivedBy = null,
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

        if ($kind === CharacterLoanLedgerEntries::KIND_INTEREST
            && in_array($type, LoanTypes::characterFamily(), true)) {
            throw new InvalidArgumentException(__('Collect character loan interest on Invoice, not Official Receipt.'));
        }

        if (in_array($type, LoanTypes::characterFamily(), true)
            && ! CharacterLoanLedgerEntries::principalUnlocked($loan)) {
            throw new InvalidArgumentException(__('Pay the ₱:amount interest on Invoice first.', [
                'amount' => number_format(CharacterLoanLedgerEntries::periodInterest($loan), 2),
            ]));
        }

        if ($loan instanceof QuickLoan && ! QuickLoanLedgerEntries::principalUnlocked($loan)) {
            throw new InvalidArgumentException(__('Pay the ₱:amount interest on Invoice first.', [
                'amount' => number_format(QuickLoanLedgerEntries::interestOn((float) $loan->loan_amount), 2),
            ]));
        }

        if ($loan instanceof RegularLoan) {
            $remaining = RegularLoanPaymentAllocation::remainingCollectable($loan);

            if ($amount - $remaining > self::EPSILON) {
                throw new InvalidArgumentException(__('Payment cannot be more than the ₱:amount remaining on this regular loan.', [
                    'amount' => number_format($remaining, 2),
                ]));
            }
        } elseif ($kind !== CharacterLoanLedgerEntries::KIND_INTEREST) {
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

        $officialReceiptNo = filled($officialReceiptNo) ? trim((string) $officialReceiptNo) : null;
        $receivedBy = $receivedBy ?? self::staffUserId();

        return DB::transaction(function () use ($loan, $amount, $kind, $officialReceiptNo, $receivedAt, $type, $receiptKind, $receivedBy): LoanPayment {
            if ($loan instanceof RegularLoan) {
                return self::storeRegularSplit($loan, $amount, $officialReceiptNo, $receivedAt, $receiptKind, $receivedBy, $kind);
            }

            return $loan->payments()->create([
                'amount' => $amount,
                'kind' => in_array($type, LoanTypes::characterFamily(), true)
                    ? $kind
                    : CharacterLoanLedgerEntries::KIND_PRINCIPAL,
                'official_receipt_no' => $officialReceiptNo,
                'receipt_kind' => $receiptKind,
                'received_at' => $receivedAt ?? now(),
                'received_by' => $receivedBy,
            ]);
        });
    }

    public static function revise(LoanPayment $payment, float $amount): LoanPayment
    {
        $loan = $payment->loan();

        if (! $loan instanceof MemberLoan) {
            throw new InvalidArgumentException(__('This loan could not be found.'));
        }

        $amount = round($amount, 2);

        if ($amount < 0.01) {
            throw new InvalidArgumentException(__('Enter a payment amount.'));
        }

        $loan->load('payments');
        $loan->setRelation(
            'payments',
            $loan->payments->where('id', '!=', $payment->id)->values(),
        );

        $kind = strtolower(trim((string) $payment->kind)) ?: CharacterLoanLedgerEntries::KIND_PRINCIPAL;

        if ($loan instanceof RegularLoan) {
            if (RegularLoanPaymentAllocation::isAdvance($payment)
                || strcasecmp($kind, RegularLoanPaymentAllocation::KIND_ADVANCE) === 0) {
                $remaining = self::remainingPrincipal($loan);

                if ($amount - $remaining > self::EPSILON) {
                    throw new InvalidArgumentException(__('Payment cannot be more than the ₱:amount remaining principal.', [
                        'amount' => number_format($remaining, 2),
                    ]));
                }

                $payment->fill([
                    'amount' => $amount,
                    'kind' => RegularLoanPaymentAllocation::KIND_ADVANCE,
                    'interest_applied' => 0.0,
                    'principal_applied' => $amount,
                ]);
                $payment->save();

                return $payment->refresh();
            }

            $remaining = RegularLoanPaymentAllocation::remainingCollectable($loan);

            if ($amount - $remaining > self::EPSILON) {
                throw new InvalidArgumentException(__('Payment cannot be more than the ₱:amount remaining on this regular loan.', [
                    'amount' => number_format($remaining, 2),
                ]));
            }

            $split = RegularLoanPaymentAllocation::splitAmount($loan, $amount);
            $payment->fill([
                'amount' => $amount,
                'interest_applied' => $split['interest'],
                'principal_applied' => $split['principal'],
            ]);
            $payment->save();

            return $payment->refresh();
        }

        if ($kind !== CharacterLoanLedgerEntries::KIND_INTEREST) {
            $remaining = self::remainingPrincipal($loan);

            if ($amount - $remaining > self::EPSILON) {
                throw new InvalidArgumentException(__('Payment cannot be more than the ₱:amount remaining principal.', [
                    'amount' => number_format($remaining, 2),
                ]));
            }
        }

        $payment->amount = $amount;

        if ($payment->received_by === null) {
            $payment->received_by = self::staffUserId();
        }

        $payment->save();

        return $payment->refresh();
    }

    protected static function storeRegularSplit(
        RegularLoan $loan,
        float $amount,
        ?string $officialReceiptNo,
        mixed $receivedAt,
        ?string $receiptKind = null,
        ?int $receivedBy = null,
        string $kind = CharacterLoanLedgerEntries::KIND_PRINCIPAL,
    ): LoanPayment {
        if (strcasecmp($kind, RegularLoanPaymentAllocation::KIND_ADVANCE) === 0) {
            $remaining = self::remainingPrincipal($loan);

            if ($amount - $remaining > self::EPSILON) {
                throw new InvalidArgumentException(__('Payment cannot be more than the ₱:amount remaining principal.', [
                    'amount' => number_format($remaining, 2),
                ]));
            }

            return $loan->payments()->create([
                'amount' => $amount,
                'kind' => RegularLoanPaymentAllocation::KIND_ADVANCE,
                'interest_applied' => 0.0,
                'principal_applied' => $amount,
                'official_receipt_no' => $officialReceiptNo,
                'receipt_kind' => $receiptKind,
                'received_at' => $receivedAt ?? now(),
                'received_by' => $receivedBy ?? self::staffUserId(),
            ]);
        }

        $split = RegularLoanPaymentAllocation::splitAmount($loan, $amount);

        return $loan->payments()->create([
            'amount' => $amount,
            'kind' => CharacterLoanLedgerEntries::KIND_PRINCIPAL,
            'interest_applied' => $split['interest'],
            'principal_applied' => $split['principal'],
            'official_receipt_no' => $officialReceiptNo,
            'receipt_kind' => $receiptKind,
            'received_at' => $receivedAt ?? now(),
            'received_by' => $receivedBy ?? self::staffUserId(),
        ]);
    }

    protected static function staffUserId(): ?int
    {
        $account = RoleDashboard::currentUser();

        return $account instanceof User ? (int) $account->id : null;
    }

    protected static function regularPrincipalApplied(LoanPayment $payment): float
    {
        if ($payment->principal_applied !== null) {
            return round((float) $payment->principal_applied, 2);
        }

        if (! CharacterLoanLedgerEntries::isPrincipalPayment($payment)) {
            return 0.0;
        }

        return round((float) $payment->amount, 2);
    }
}
