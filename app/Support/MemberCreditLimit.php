<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\Member;

/**
 * How much a member may still charge to their account. Grocery and canteen each
 * have their own ₱6,000 cap on outstanding balance. Paying down the balance frees
 * room immediately; the full limit is available again only when the channel is
 * fully paid.
 */
final class MemberCreditLimit
{
    /** Per member, per channel — outstanding balance may not exceed this. */
    public const LIMIT = 6000.00;

    /** @deprecated Use LIMIT. Kept so older call sites keep working. */
    public const MONTHLY_LIMIT = self::LIMIT;

    /** Amounts within half a centavo of the limit are treated as equal. */
    private const EPSILON = 0.005;

    public static function used(int $memberId, PosSaleChannel $channel): float
    {
        $member = Member::query()->find($memberId);

        if (! $member instanceof Member) {
            return 0.0;
        }

        return (new MemberPosCredit($member))->outstandingFor($channel);
    }

    public static function remaining(int $memberId, PosSaleChannel $channel): float
    {
        return round(max(0, self::LIMIT - self::used($memberId, $channel)), 2);
    }

    /** @deprecated Use used() — limit is no longer calendar-month based. */
    public static function usedThisMonth(int $memberId, PosSaleChannel $channel, mixed $month = null): float
    {
        return self::used($memberId, $channel);
    }

    /** @deprecated Use remaining() — limit is no longer calendar-month based. */
    public static function remainingThisMonth(int $memberId, PosSaleChannel $channel, mixed $month = null): float
    {
        return self::remaining($memberId, $channel);
    }

    public static function allows(float $creditPortion, float $remaining): bool
    {
        return $creditPortion <= $remaining + self::EPSILON;
    }

    /**
     * Cash the cashier has to collect now so the rest of the sale fits within
     * what the member still has available on their credit limit.
     */
    public static function minimumCashDue(float $total, float $remaining): float
    {
        return round(max(0, $total - $remaining), 2);
    }
}
