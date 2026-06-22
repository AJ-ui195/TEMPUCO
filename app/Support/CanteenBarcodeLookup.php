<?php

namespace App\Support;

use App\Models\PosCanteenInventoryItem;

class CanteenBarcodeLookup
{
    /**
     * @return array{product: PosCanteenInventoryItem, available_quantity: int}|null
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

    protected static function findProduct(string $barcode): ?PosCanteenInventoryItem
    {
        $barcode = trim($barcode);

        if ($barcode === '') {
            return null;
        }

        return PosCanteenInventoryItem::query()
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
