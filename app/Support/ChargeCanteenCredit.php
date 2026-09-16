<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\Member;
use App\Models\PosSale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ChargeCanteenCredit
{
    /**
     * @return array{sale: PosSale, remaining_limit: float}
     */
    public static function apply(Member $member, float $amount, ?User $cashier = null): array
    {
        $amount = round($amount, 2);

        if ($amount < 0.01) {
            throw new InvalidArgumentException(__('Enter the amount to charge to the canteen account.'));
        }

        $channel = PosSaleChannel::Canteen;
        $remaining = MemberCreditLimit::remaining($member->id, $channel);

        if (! MemberCreditLimit::allows($amount, $remaining)) {
            throw new InvalidArgumentException(__('This charge exceeds the ₱:limit canteen credit limit. Remaining: ₱:remaining. Settle the account to free credit.', [
                'limit' => number_format(MemberCreditLimit::LIMIT, 2),
                'remaining' => number_format($remaining, 2),
            ]));
        }

        $sale = DB::transaction(function () use ($member, $amount, $cashier, $channel): PosSale {
            return PosSale::query()->create([
                'pos_branch_id' => null,
                'member_id' => $member->id,
                'cashier_id' => $cashier?->id,
                'sale_channel' => $channel,
                'total' => $amount,
                'amount_paid' => 0,
                'change_amount' => 0,
                'reference' => self::generateReference(),
            ]);
        });

        return [
            'sale' => $sale,
            'remaining_limit' => round(max(0, $remaining - $amount), 2),
        ];
    }

    private static function generateReference(): string
    {
        do {
            $reference = 'CCR-'.PhilippineTime::now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (PosSale::referenceExists($reference));

        return $reference;
    }
}
