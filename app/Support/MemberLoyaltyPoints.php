<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class MemberLoyaltyPoints
{
    public const THRESHOLD_PESOS = 200;

    public const POINTS_PER_QUALIFYING_SALE = 1;

    public static function qualifies(float $total): bool
    {
        return $total >= self::THRESHOLD_PESOS;
    }

    /**
     * Award loyalty points for a qualifying sale. Returns points awarded (0 or 1).
     */
    public static function awardIfEligible(?int $memberId, float $total): int
    {
        if ($memberId === null || ! self::qualifies($total)) {
            return 0;
        }

        if (! Schema::hasColumn('users', 'points')) {
            return 0;
        }

        User::query()
            ->whereKey($memberId)
            ->increment('points', self::POINTS_PER_QUALIFYING_SALE);

        return self::POINTS_PER_QUALIFYING_SALE;
    }
}
