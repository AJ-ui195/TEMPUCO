<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
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
    public static function apply(User $member, PosSaleChannel $channel, float $amount, ?User $cashier = null): array
    {
        return DB::transaction(function () use ($member, $channel, $amount, $cashier): array {
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

            $payment = PosCreditPayment::query()->create([
                'member_id' => $member->id,
                'cashier_id' => $cashier?->id,
                'sale_channel' => $channel,
                'amount' => $applied,
                'reference' => self::generateReference(),
            ]);

            return [
                'applied' => $applied,
                'remaining_balance' => round(max(0, $outstanding - $applied), 2),
                'payment' => $payment,
            ];
        });
    }

    private static function generateReference(): string
    {
        do {
            $reference = 'CRP-'.PhilippineTime::now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (PosCreditPayment::referenceExists($reference));

        return $reference;
    }
}
