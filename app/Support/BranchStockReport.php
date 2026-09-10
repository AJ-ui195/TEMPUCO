<?php

namespace App\Support;

use App\Models\PosBranch;
use App\Models\PosBranchInventory;
use App\Models\PosBranchStockTransfer;
use Illuminate\Support\Collection;

final class BranchStockReport
{
    public function __construct(private readonly PosBranch $branch) {}

    /**
     * @return array{products: int, units: int}
     */
    public function stockLeftTotals(): array
    {
        $query = PosBranchInventory::query()
            ->where('pos_branch_id', $this->branch->id)
            ->where('quantity', '>', 0);

        return [
            'products' => (int) (clone $query)->count(),
            'units' => (int) (clone $query)->sum('quantity'),
        ];
    }

    /**
     * @return array{products: int, units: int}
     */
    public function transferTotals(): array
    {
        $query = PosBranchStockTransfer::query()
            ->where('pos_branch_id', $this->branch->id);

        return [
            'products' => (int) (clone $query)->distinct()->count('pos_inventory_item_id'),
            'units' => (int) (clone $query)->sum('quantity'),
        ];
    }

    /**
     * @return Collection<int, PosBranchInventory>
     */
    public function stockLeftRows(): Collection
    {
        return PosBranchInventory::query()
            ->where('pos_branch_id', $this->branch->id)
            ->where('quantity', '>', 0)
            ->with('inventoryItem')
            ->get()
            ->sortBy(fn (PosBranchInventory $row): string => $row->inventoryItem?->name ?? '')
            ->values();
    }

    /**
     * @return Collection<int, PosBranchStockTransfer>
     */
    public function transferRows(): Collection
    {
        return PosBranchStockTransfer::query()
            ->where('pos_branch_id', $this->branch->id)
            ->with(['inventoryItem', 'recordedBy'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
