<?php

namespace App\Support;

use App\Models\PosBranchInventory;
use App\Models\PosInventoryItem;

class PosBarcodeLookup
{
    /**
     * @return array{product: PosInventoryItem, available_quantity: int}|null
     */
    public static function find(string $barcode): ?array
    {
        $product = self::findProduct($barcode);

        if (! $product) {
            return null;
        }

        return [
            'product' => $product,
            'available_quantity' => (int) $product->quantity,
        ];
    }

    /**
     * @return array{product: PosInventoryItem, branch_quantity: int}|null
     */
    public static function findForBranch(string $barcode, int $branchId): ?array
    {
        $product = self::findProduct($barcode);

        if (! $product) {
            return null;
        }

        $branchQuantity = (int) (PosBranchInventory::query()
            ->where('pos_branch_id', $branchId)
            ->where('pos_inventory_item_id', $product->id)
            ->value('quantity') ?? 0);

        return [
            'product' => $product,
            'branch_quantity' => $branchQuantity,
        ];
    }

    protected static function findProduct(string $barcode): ?PosInventoryItem
    {
        $barcode = trim($barcode);

        if ($barcode === '') {
            return null;
        }

        return PosInventoryItem::query()
            ->where('is_active', true)
            ->where(function ($query) use ($barcode): void {
                $query->where('sku', $barcode);

                if (ctype_digit($barcode)) {
                    $query->orWhere('id', (int) $barcode);
                }
            })
            ->first();
    }
}
