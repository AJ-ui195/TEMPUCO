<?php

namespace App\Support;

use App\Models\PosInventoryDamage;
use App\Models\PosInventoryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pulls damaged units out of grocery stock. The quantity leaves
 * `pos_inventory_items` and the reason is kept in `pos_inventory_damages`, which
 * is what the close inventory report reads for its pull-out column.
 */
final class RecordInventoryDamage
{
    /**
     * @param  array<int, array{pos_inventory_item_id: mixed, quantity: mixed, reason: mixed}>  $lines
     * @return array<int, array{item: PosInventoryItem, quantity: int}>
     */
    public static function recordMany(array $lines, ?User $user = null): array
    {
        $prepared = [];

        foreach ($lines as $line) {
            $itemId = (int) ($line['pos_inventory_item_id'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);
            $reason = trim((string) ($line['reason'] ?? ''));

            if ($itemId < 1 || $quantity < 1) {
                continue;
            }

            if ($reason === '') {
                throw ValidationException::withMessages([
                    'data.items' => __('Give the reason for each damaged product.'),
                ]);
            }

            $prepared[] = [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'reason' => $reason,
            ];
        }

        if ($prepared === []) {
            throw ValidationException::withMessages([
                'data.items' => __('Add at least one damaged product.'),
            ]);
        }

        return DB::transaction(function () use ($prepared, $user): array {
            $items = PosInventoryItem::query()
                ->whereIn('id', array_column($prepared, 'item_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $requestedByItem = [];

            foreach ($prepared as $line) {
                $requestedByItem[$line['item_id']] = ($requestedByItem[$line['item_id']] ?? 0) + $line['quantity'];
            }

            foreach ($requestedByItem as $itemId => $quantity) {
                $item = $items->get($itemId);

                if (! $item) {
                    throw ValidationException::withMessages([
                        'data.items' => __('One or more selected products are not available.'),
                    ]);
                }

                if ($item->quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'data.items' => __('Only :count unit(s) of :product left in stock.', [
                            'count' => $item->quantity,
                            'product' => $item->name,
                        ]),
                    ]);
                }
            }

            $recorded = [];

            foreach ($prepared as $line) {
                $item = $items->get($line['item_id']);

                PosInventoryDamage::query()->create([
                    'pos_inventory_item_id' => $item->id,
                    'recorded_by' => $user?->id,
                    'quantity' => $line['quantity'],
                    'reason' => $line['reason'],
                ]);

                $item->decrement('quantity', $line['quantity']);

                $recorded[] = [
                    'item' => $item->fresh(),
                    'quantity' => $line['quantity'],
                ];
            }

            return $recorded;
        });
    }

    /**
     * Units pulled out per product, keyed by inventory item id.
     *
     * @param  array<int, int>|null  $itemIds
     * @return array<int, int>
     */
    public static function quantitiesByItem(?array $itemIds = null): array
    {
        return PosInventoryDamage::query()
            ->when($itemIds !== null, fn ($query) => $query->whereIn('pos_inventory_item_id', $itemIds))
            ->selectRaw('pos_inventory_item_id, SUM(quantity) as pulled')
            ->groupBy('pos_inventory_item_id')
            ->pluck('pulled', 'pos_inventory_item_id')
            ->map(fn ($pulled): int => (int) $pulled)
            ->all();
    }
}
