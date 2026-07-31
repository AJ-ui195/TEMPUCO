<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\PosCreditPayment;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * A member's POS account balance, derived from what was charged to the account
 * at checkout minus the payments they have made since. Nothing is stored twice:
 * sales keep the amount tendered at the register, payments live in
 * `pos_credit_payments`, and every balance here is calculated from those two.
 */
class MemberPosCredit
{
    /** Balances below one centavo are treated as settled. */
    public const EPSILON = 0.005;

    public function __construct(
        public User $user,
    ) {}

    public function groceryOutstanding(): float
    {
        return $this->outstandingFor(PosSaleChannel::Grocery);
    }

    public function canteenOutstanding(): float
    {
        return $this->outstandingFor(PosSaleChannel::Canteen);
    }

    public function totalOutstanding(): float
    {
        return round($this->groceryOutstanding() + $this->canteenOutstanding(), 2);
    }

    /** Total put on account at checkout for this channel. */
    public function chargedFor(PosSaleChannel $channel): float
    {
        return round((float) $this->creditSalesQuery($channel)
            ->selectRaw('SUM(total - amount_paid) as charged')
            ->value('charged'), 2);
    }

    /** Total the member has since paid against this channel. */
    public function paidFor(PosSaleChannel $channel): float
    {
        return round((float) $this->paymentsQuery($channel)->sum('amount'), 2);
    }

    public function outstandingFor(PosSaleChannel $channel): float
    {
        return round(max(0, $this->chargedFor($channel) - $this->paidFor($channel)), 2);
    }

    /**
     * Charges that still have a balance, oldest first, with payments applied in
     * the same order they were received.
     *
     * @return Collection<int, array{sale: PosSale, charged: float, settled: float, balance: float}>
     */
    public function openCharges(PosSaleChannel $channel): Collection
    {
        $pool = $this->paidFor($channel);

        return $this->creditSalesQuery($channel)
            ->with(['items.inventoryItem', 'items.canteenInventoryItem'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (PosSale $sale) use (&$pool): array {
                $charged = $sale->outstandingAmount();
                $settled = min($pool, $charged);
                $pool = round($pool - $settled, 2);

                return [
                    'sale' => $sale,
                    'charged' => $charged,
                    'settled' => round($settled, 2),
                    'balance' => round($charged - $settled, 2),
                ];
            })
            ->filter(fn (array $row): bool => $row['balance'] > self::EPSILON)
            ->values();
    }

    /**
     * @return Collection<int, PosSale>
     */
    public function unpaidSales(PosSaleChannel $channel): Collection
    {
        return $this->openCharges($channel)->map(fn (array $row): PosSale => $row['sale']);
    }

    /**
     * Line items from charges that still have a balance, flattened for display.
     *
     * @return Collection<int, array{date: string, name: string, quantity: int, unit_price: float, line_total: float, reference: string}>
     */
    public function unpaidLineItems(PosSaleChannel $channel): Collection
    {
        return $this->unpaidSales($channel)
            ->flatMap(function (PosSale $sale): Collection {
                return $sale->items->map(fn (PosSaleItem $item): array => [
                    'date' => PhilippineTime::format($sale->created_at),
                    'name' => $item->productName(),
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                    'reference' => $sale->reference,
                ]);
            })
            ->values();
    }

    /**
     * @return Builder<PosSale>
     */
    public function creditSalesQuery(PosSaleChannel $channel): Builder
    {
        return PosSale::query()
            ->where('member_id', $this->user->id)
            ->where('sale_channel', $channel->value)
            ->whereColumn('amount_paid', '<', 'total');
    }

    /**
     * @return Builder<PosCreditPayment>
     */
    public function paymentsQuery(PosSaleChannel $channel): Builder
    {
        return PosCreditPayment::query()
            ->where('member_id', $this->user->id)
            ->where('sale_channel', $channel->value);
    }
}
