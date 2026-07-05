<?php

namespace App\Support;

use App\Models\PosBranch;
use App\Models\PosBranchInventory;
use App\Models\PosInventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchStockTransfer
{
    /**
     * @param  array<int, array{pos_inventory_item_id: mixed, quantity: mixed}>  $items
     * @return array<int, array{item: PosInventoryItem, quantity: int}>
     */
    public static function transferMany(int $branchId, array $items): array
    {
        $quantitiesByItem = self::aggregateQuantities($items);

        if ($quantitiesByItem === []) {
            throw ValidationException::withMessages([
                'data.items' => __('Add at least one product to transfer.'),
            ]);
        }

        return DB::transaction(function () use ($branchId, $quantitiesByItem): array {
            $branch = PosBranch::query()
                ->where('is_active', true)
                ->find($branchId);

            if (! $branch) {
                throw ValidationException::withMessages([
                    'data.pos_branch_id' => __('The selected branch is not available.'),
                ]);
            }

            $inventoryItems = PosInventoryItem::query()
                ->active()
                ->whereIn('id', array_keys($quantitiesByItem))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $transferred = [];

            foreach ($quantitiesByItem as $itemId => $quantity) {
                $item = $inventoryItems->get($itemId);

                if (! $item) {
                    throw ValidationException::withMessages([
                        'data.items' => __('One or more selected products are not available.'),
                    ]);
                }

                if ($item->quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'data.items' => __('Only :count unit(s) of :product available in warehouse inventory.', [
                            'count' => $item->quantity,
                            'product' => $item->name,
                        ]),
                    ]);
                }
            }

            foreach ($quantitiesByItem as $itemId => $quantity) {
                $item = $inventoryItems->get($itemId);

                $branchInventory = PosBranchInventory::query()
                    ->where('pos_branch_id', $branch->id)
                    ->where('pos_inventory_item_id', $item->id)
                    ->lockForUpdate()
                    ->first();

                if (! $branchInventory) {
                    $branchInventory = PosBranchInventory::query()->create([
                        'pos_branch_id' => $branch->id,
                        'pos_inventory_item_id' => $item->id,
                        'quantity' => 0,
                    ]);
                }

                $item->decrement('quantity', $quantity);
                $branchInventory->increment('quantity', $quantity);

                $transferred[] = [
                    'item' => $item->fresh(),
                    'quantity' => $quantity,
                ];
            }

            return $transferred;
        });
    }

    public static function transfer(int $branchId, int $itemId, int $quantity): void
    {
        self::transferMany($branchId, [
            [
                'pos_inventory_item_id' => $itemId,
                'quantity' => $quantity,
            ],
        ]);
    }

    /**
     * @param  array<int, array{pos_inventory_item_id: mixed, quantity: mixed}>  $items
     * @return array<int, int>
     */
    private static function aggregateQuantities(array $items): array
    {
        $quantitiesByItem = [];

        foreach ($items as $line) {
            $itemId = (int) ($line['pos_inventory_item_id'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);

            if ($itemId < 1 || $quantity < 1) {
                continue;
            }

            $quantitiesByItem[$itemId] = ($quantitiesByItem[$itemId] ?? 0) + $quantity;
        }

        return $quantitiesByItem;
    }
}
