<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MemberPosCredit
{
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

    public function outstandingFor(PosSaleChannel $channel): float
    {
        return round((float) $this->unpaidSalesQuery($channel)
            ->selectRaw('SUM(total - amount_paid) as outstanding')
            ->value('outstanding'), 2);
    }

    /**
     * @return Collection<int, PosSale>
     */
    public function unpaidSales(PosSaleChannel $channel): Collection
    {
        return $this->unpaidSalesQuery($channel)
            ->with(['items.inventoryItem'])
            ->latest()
            ->get();
    }

    /**
     * Line items from unsettled sales, flattened for display.
     *
     * @return Collection<int, array{date: string, name: string, quantity: int, unit_price: float, line_total: float, reference: string}>
     */
    public function unpaidLineItems(PosSaleChannel $channel): Collection
    {
        return $this->unpaidSales($channel)
            ->flatMap(function (PosSale $sale): Collection {
                return $sale->items->map(fn (PosSaleItem $item): array => [
                    'date' => PhilippineTime::format($sale->created_at),
                    'name' => $item->inventoryItem?->name ?? __('Unknown item'),
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
    protected function unpaidSalesQuery(PosSaleChannel $channel): Builder
    {
        return PosSale::query()
            ->where('user_id', $this->user->id)
            ->where('sale_channel', $channel->value)
            ->whereColumn('amount_paid', '<', 'total');
    }
}
