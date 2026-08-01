<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\PosSale;
use Illuminate\Support\Carbon;

/**
 * How much a member may still charge to their account this month. Grocery and
 * canteen each have their own allowance, and it is measured on what was put on
 * account during the calendar month — settling an old balance does not free up
 * more room until the next month.
 */
final class MemberCreditLimit
{
    /** Per member, per channel, per calendar month. */
    public const MONTHLY_LIMIT = 6000.00;

    /** Amounts within half a centavo of the limit are treated as equal. */
    private const EPSILON = 0.005;

    public static function usedThisMonth(int $memberId, PosSaleChannel $channel, ?Carbon $month = null): float
    {
        $month ??= PhilippineTime::now();

        return round((float) PosSale::query()
            ->where('member_id', $memberId)
            ->where('sale_channel', $channel->value)
            ->whereColumn('amount_paid', '<', 'total')
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->selectRaw('SUM(total - amount_paid) as charged')
            ->value('charged'), 2);
    }

    public static function remainingThisMonth(int $memberId, PosSaleChannel $channel, ?Carbon $month = null): float
    {
        return round(max(0, self::MONTHLY_LIMIT - self::usedThisMonth($memberId, $channel, $month)), 2);
    }

    public static function allows(float $creditPortion, float $remaining): bool
    {
        return $creditPortion <= $remaining + self::EPSILON;
    }

    /**
     * Cash the cashier has to collect now so the rest of the sale fits within
     * what the member has left for the month.
     */
    public static function minimumCashDue(float $total, float $remaining): float
    {
        return round(max(0, $total - $remaining), 2);
    }
}
