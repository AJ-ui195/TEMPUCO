<?php

namespace App\Support;

use App\Models\PosInventoryItem;
use Illuminate\Support\Collection;

final class CloseInventoryReport
{
    /**
     * @return Collection<int, array{
     *     item_code: string,
     *     item_name: string,
     *     selling_price: float,
     *     beginning: int,
     *     sales: int,
     *     pullout: int,
     *     delivery: int,
     *     return: int,
     *     adjustment: int,
     *     balance: int,
     *     unit_cost: float,
     *     total: float
     * }>
     */
    public function rows(): Collection
    {
        $pullOutByItem = RecordInventoryDamage::quantitiesByItem();

        return PosInventoryItem::query()
            ->orderedByName()
            ->get()
            ->map(function (PosInventoryItem $item) use ($pullOutByItem): array {
                $balance = (int) $item->quantity;
                $unitCost = (float) $item->cost;

                return [
                    'item_code' => (string) ($item->sku ?? ''),
                    'item_name' => (string) $item->name,
                    'selling_price' => (float) $item->unit_price,
                    'beginning' => 0,
                    'sales' => 0,
                    'pullout' => $pullOutByItem[$item->id] ?? 0,
                    'delivery' => 0,
                    'return' => 0,
                    'adjustment' => 0,
                    'balance' => $balance,
                    'unit_cost' => $unitCost,
                    'total' => round($balance * $unitCost, 2),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array{
     *     item_code: string,
     *     item_name: string,
     *     selling_price: float,
     *     beginning: int,
     *     sales: int,
     *     pullout: int,
     *     delivery: int,
     *     return: int,
     *     adjustment: int,
     *     balance: int,
     *     unit_cost: float,
     *     total: float
     * }>|null  $rows
     * @return array{
     *     selling_price: float,
     *     beginning: int,
     *     sales: int,
     *     pullout: int,
     *     delivery: int,
     *     return: int,
     *     adjustment: int,
     *     balance: int,
     *     unit_cost: float,
     *     total: float
     * }
     */
    public function totals(?Collection $rows = null): array
    {
        $rows ??= $this->rows();

        return [
            'selling_price' => round((float) $rows->sum('selling_price'), 2),
            'beginning' => (int) $rows->sum('beginning'),
            'sales' => (int) $rows->sum('sales'),
            'pullout' => (int) $rows->sum('pullout'),
            'delivery' => (int) $rows->sum('delivery'),
            'return' => (int) $rows->sum('return'),
            'adjustment' => (int) $rows->sum('adjustment'),
            'balance' => (int) $rows->sum('balance'),
            'unit_cost' => round((float) $rows->sum('unit_cost'), 2),
            'total' => round((float) $rows->sum('total'), 2),
        ];
    }
}
