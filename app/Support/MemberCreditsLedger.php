<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\PosSale;
use App\Models\User;
use Illuminate\Support\Collection;

final class MemberCreditsLedger
{
    /**
     * @return Collection<int, array{
     *     member: User,
     *     grocery_outstanding: float,
     *     canteen_outstanding: float,
     *     total_outstanding: float,
     *     items: Collection<int, array{channel: string, date: string, name: string, quantity: int, unit_price: float, line_total: float, reference: string}>
     * }>
     */
    public static function membersWithCredit(): Collection
    {
        $memberIds = PosSale::query()
            ->whereColumn('amount_paid', '<', 'total')
            ->whereHas('user', fn ($query) => $query->members())
            ->distinct()
            ->pluck('user_id');

        if ($memberIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->members()
            ->whereIn('id', $memberIds)
            ->orderedByName()
            ->get()
            ->map(function (User $member): array {
                $credit = new MemberPosCredit($member);

                $items = $credit->unpaidLineItems(PosSaleChannel::Grocery)
                    ->map(fn (array $item): array => array_merge($item, ['channel' => __('Grocery')]))
                    ->concat(
                        $credit->unpaidLineItems(PosSaleChannel::Canteen)
                            ->map(fn (array $item): array => array_merge($item, ['channel' => __('Canteen')]))
                    )
                    ->values();

                return [
                    'member' => $member,
                    'grocery_outstanding' => $credit->groceryOutstanding(),
                    'canteen_outstanding' => $credit->canteenOutstanding(),
                    'total_outstanding' => $credit->totalOutstanding(),
                    'items' => $items,
                ];
            })
            ->filter(function (array $row): bool {
                return $row['total_outstanding'] > 0;
            })
            ->sortByDesc(function (array $row): float {
                return $row['total_outstanding'];
            })
            ->values();
    }

    public static function totalOutstanding(): float
    {
        $total = 0.0;

        foreach (self::membersWithCredit() as $row) {
            $total += $row['total_outstanding'];
        }

        return round($total, 2);
    }
}
