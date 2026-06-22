<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\PosSaleItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FastMovingItemsReport
{
    public function __construct(
        public int $days = 30,
        public ?PosSaleChannel $saleChannel = null,
    ) {}

    /**
     * @return Collection<int, array{rank: int, name: string, sku: ?string, quantity_sold: int, revenue: float}>
     */
    public function top(int $limit = 10): Collection
    {
        $since = now()->subDays($this->days)->startOfDay();
        $isCanteen = $this->saleChannel === PosSaleChannel::Canteen;
        $itemForeignKey = $isCanteen ? 'pos_canteen_inventory_item_id' : 'pos_inventory_item_id';
        $relation = $isCanteen ? 'canteenInventoryItem' : 'inventoryItem';

        return PosSaleItem::query()
            ->select([
                $itemForeignKey,
                DB::raw('SUM(quantity) as quantity_sold'),
                DB::raw('SUM(line_total) as revenue'),
            ])
            ->whereNotNull($itemForeignKey)
            ->whereHas('sale', function ($query) use ($since): void {
                $query->where('created_at', '>=', $since);

                if ($this->saleChannel !== null) {
                    $query->where('sale_channel', $this->saleChannel);
                }
            })
            ->with("{$relation}:id,name,sku")
            ->groupBy($itemForeignKey)
            ->orderByDesc('quantity_sold')
            ->limit($limit)
            ->get()
            ->values()
            ->map(function (PosSaleItem $row, int $index) use ($relation): array {
                $product = $row->{$relation};

                return [
                    'rank' => $index + 1,
                    'name' => $product?->name ?? __('Unknown product'),
                    'sku' => $product?->sku,
                    'quantity_sold' => (int) $row->quantity_sold,
                    'revenue' => (float) $row->revenue,
                ];
            });
    }

    public function periodLabel(): string
    {
        return __('Last :days days', ['days' => $this->days]);
    }
}
