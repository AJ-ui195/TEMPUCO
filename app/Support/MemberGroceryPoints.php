<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\Member;
use App\Models\PosSale;

/**
 * Grocery loyalty (cash only): 1 point for every full ₱200 of a member's
 * grocery cash purchase. Stored on members.points. ₱400 → 2 points.
 */
final class MemberGroceryPoints
{
    public const PESOS_PER_POINT = 200.0;

    public static function pointsForAmount(float $amount): int
    {
        if ($amount < self::PESOS_PER_POINT) {
            return 0;
        }

        return (int) floor($amount / self::PESOS_PER_POINT);
    }

    /** Grocery + member + fully paid in cash (no outstanding credit). */
    public static function qualifies(PosSale $sale, ?float $total = null): bool
    {
        if ($sale->sale_channel !== PosSaleChannel::Grocery || ! $sale->hasMember()) {
            return false;
        }

        $saleTotal = $total ?? (float) $sale->total;

        return (float) $sale->amount_paid + MemberPosCredit::EPSILON >= $saleTotal;
    }

    /**
     * Award points for a completed grocery cash sale linked to a member.
     * Increments members.points.
     *
     * @return int Points added this sale
     */
    public static function awardForSale(PosSale $sale): int
    {
        if (! self::qualifies($sale)) {
            return 0;
        }

        $points = self::pointsForAmount((float) $sale->total);

        if ($points < 1) {
            return 0;
        }

        Member::query()->whereKey($sale->member_id)->increment('points', $points);

        return $points;
    }

    /**
     * After a grocery cash sale total shrinks from voids, take back excess points.
     *
     * @return int Points removed from the member
     */
    public static function adjustAfterVoid(PosSale $sale, float $previousTotal, float $newTotal): int
    {
        // Only cash sales earned points — amount_paid still reflects what was tendered.
        if (! self::qualifies($sale, $previousTotal)) {
            return 0;
        }

        $previousPoints = self::pointsForAmount($previousTotal);
        $newPoints = self::pointsForAmount($newTotal);
        $remove = max(0, $previousPoints - $newPoints);

        if ($remove < 1) {
            return 0;
        }

        $member = Member::query()->whereKey($sale->member_id)->lockForUpdate()->first();

        if (! $member instanceof Member) {
            return 0;
        }

        $member->forceFill([
            'points' => max(0, (int) $member->points - $remove),
        ])->save();

        return $remove;
    }
}
