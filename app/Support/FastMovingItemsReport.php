<?php

namespace App\Support;

use App\Models\PosSaleItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FastMovingItemsReport
{
    public function __construct(
        public int $days = 30,
    ) {}

    /**
     * @return Collection<int, array{rank: int, name: string, sku: ?string, quantity_sold: int, revenue: float}>
     */
    public function top(int $limit = 10): Collection
    {
        $since = now()->subDays($this->days)->startOfDay();

        return PosSaleItem::query()
            ->select([
                'pos_inventory_item_id',
                DB::raw('SUM(quantity) as quantity_sold'),
                DB::raw('SUM(line_total) as revenue'),
            ])
            ->whereHas('sale', fn ($query) => $query->where('created_at', '>=', $since))
            ->with('inventoryItem:id,name,sku')
            ->groupBy('pos_inventory_item_id')
            ->orderByDesc('quantity_sold')
            ->limit($limit)
            ->get()
            ->values()
            ->map(fn (PosSaleItem $row, int $index): array => [
                'rank' => $index + 1,
                'name' => $row->inventoryItem?->name ?? __('Unknown product'),
                'sku' => $row->inventoryItem?->sku,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => (float) $row->revenue,
            ]);
    }

    public function periodLabel(): string
    {
        return __('Last :days days', ['days' => $this->days]);
    }
}
