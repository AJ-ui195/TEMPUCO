<?php

namespace App\Support;

use App\Models\PosCanteenInventoryItem;
use App\Models\PosInventoryItem;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSaleItemVoid;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Voids lines on a completed sale. The line stays on the sale for audit and
 * gains a void record; the stock goes back to the shelf and the sale total is
 * rebuilt from the lines that survive, which also shrinks any member credit
 * that was charged for it.
 */
final class VoidPosSaleItems
{
    /**
     * @param  array<int, int>  $saleItemIds
     * @return array{voided: int, restored_units: int, new_total: float, fully_voided: bool}
     */
    public static function void(PosSale $sale, array $saleItemIds, string $reason, ?User $cashier = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $saleItemIds))));

        if ($ids === []) {
            return [
                'voided' => 0,
                'restored_units' => 0,
                'new_total' => (float) $sale->total,
                'fully_voided' => $sale->isFullyVoided(),
            ];
        }

        return DB::transaction(function () use ($sale, $ids, $reason, $cashier): array {
            $lines = PosSaleItem::query()
                ->where('pos_sale_id', $sale->id)
                ->whereIn('id', $ids)
                ->notVoided()
                ->lockForUpdate()
                ->get();

            $restoredUnits = 0;

            foreach ($lines as $line) {
                PosSaleItemVoid::query()->create([
                    'pos_sale_item_id' => $line->id,
                    'voided_by' => $cashier?->id,
                    'reason' => $reason,
                ]);

                self::restoreStock($line);

                $restoredUnits += (int) $line->quantity;
            }

            $newTotal = round((float) PosSaleItem::query()
                ->where('pos_sale_id', $sale->id)
                ->notVoided()
                ->sum('line_total'), 2);

            $sale->forceFill(['total' => $newTotal])->save();

            return [
                'voided' => $lines->count(),
                'restored_units' => $restoredUnits,
                'new_total' => $newTotal,
                'fully_voided' => $sale->fresh()?->isFullyVoided() ?? false,
            ];
        });
    }

    /**
     * @return array{voided: int, restored_units: int, new_total: float, fully_voided: bool}
     */
    public static function voidWholeSale(PosSale $sale, string $reason, ?User $cashier = null): array
    {
        return self::void(
            $sale,
            $sale->activeItems()->pluck('id')->all(),
            $reason,
            $cashier,
        );
    }

    private static function restoreStock(PosSaleItem $line): void
    {
        $quantity = (int) $line->quantity;

        if ($quantity < 1) {
            return;
        }

        if ($line->pos_inventory_item_id !== null) {
            PosInventoryItem::query()
                ->whereKey($line->pos_inventory_item_id)
                ->lockForUpdate()
                ->first()
                ?->increment('quantity', $quantity);

            return;
        }

        if ($line->pos_canteen_inventory_item_id !== null) {
            PosCanteenInventoryItem::query()
                ->whereKey($line->pos_canteen_inventory_item_id)
                ->lockForUpdate()
                ->first()
                ?->increment('quantity', $quantity);
        }
    }
}
