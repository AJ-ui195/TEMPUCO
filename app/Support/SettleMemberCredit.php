<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\Member;
use App\Models\PosCreditPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SettleMemberCredit
{
    /** Amounts below this are treated as fully settled (peso centavo precision). */
    public const EPSILON = MemberPosCredit::EPSILON;

    /**
     * Record a payment against a member's account. Paying less than the balance
     * leaves the rest owing; the payment is never allowed to exceed the balance.
     *
     * @return array{applied: float, remaining_balance: float, payment: ?PosCreditPayment}
     */
    public static function apply(
        Member $member,
        PosSaleChannel $channel,
        float $amount,
        ?User $cashier = null,
        ?string $orNumber = null,
        ?string $invoiceNo = null,
        ?string $receiptKind = null,
    ): array {
        return DB::transaction(function () use ($member, $channel, $amount, $cashier, $orNumber, $invoiceNo, $receiptKind): array {
            $credit = new MemberPosCredit($member);
            $outstanding = $credit->outstandingFor($channel);
            $applied = round(min(round($amount, 2), $outstanding), 2);

            if ($applied < self::EPSILON) {
                return [
                    'applied' => 0.0,
                    'remaining_balance' => $outstanding,
                    'payment' => null,
                ];
            }

            $invoice = trim((string) $invoiceNo);

            $payment = PosCreditPayment::query()->create([
                'member_id' => $member->id,
                'cashier_id' => $cashier?->id,
                'sale_channel' => $channel,
                'amount' => $applied,
                'reference' => self::resolveReference($orNumber),
                'invoice_no' => $invoice !== '' ? $invoice : null,
                'receipt_kind' => $receiptKind,
            ]);

            return [
                'applied' => $applied,
                'remaining_balance' => round(max(0, $outstanding - $applied), 2),
                'payment' => $payment,
            ];
        });
    }

    /**
     * Apply cash to canteen first, then grocery, so one collection covers both ledgers.
     *
     * @return array{applied: float, remaining_balance: float, payment: ?PosCreditPayment}
     */
    public static function applyAcrossChannels(
        Member $member,
        float $amount,
        ?User $cashier = null,
        ?string $orNumber = null,
        ?string $invoiceNo = null,
        ?string $receiptKind = null,
    ): array {
        $amount = round($amount, 2);
        $credit = new MemberPosCredit($member);
        $total = $credit->totalOutstanding();
        $applied = 0.0;
        $lastPayment = null;

        foreach ([PosSaleChannel::Canteen, PosSaleChannel::Grocery] as $channel) {
            if ($amount < self::EPSILON) {
                break;
            }

            $result = self::apply($member, $channel, $amount, $cashier, $orNumber, $invoiceNo, $receiptKind);
            $applied = round($applied + $result['applied'], 2);
            $amount = round($amount - $result['applied'], 2);

            if ($result['payment'] instanceof PosCreditPayment) {
                $lastPayment = $result['payment'];
            }
        }

        return [
            'applied' => $applied,
            'remaining_balance' => round(max(0, $total - $applied), 2),
            'payment' => $lastPayment,
        ];
    }

    private static function resolveReference(?string $orNumber): string
    {
        $orNumber = trim((string) $orNumber);

        if ($orNumber !== '') {
            return $orNumber;
        }

        return self::generateReference();
    }

    private static function generateReference(): string
    {
        do {
            $reference = 'CRP-'.PhilippineTime::now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (PosCreditPayment::referenceExists($reference));

        return $reference;
    }
}
