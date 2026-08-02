<?php

namespace App\Support;

use App\Models\PosBranch;
use App\Models\PosBranchInventory;
use App\Models\PosInventoryItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchStockTransfer
{
    /**
     * @param  array<int, array{pos_inventory_item_id: mixed, quantity: mixed, expiration_date?: mixed}>  $items
     * @return array<int, array{item: PosInventoryItem, quantity: int}>
     */
    public static function transferMany(int $branchId, array $items): array
    {
        $lines = self::aggregateLines($items);

        if ($lines === []) {
            throw ValidationException::withMessages([
                'data.items' => __('Add at least one product to transfer.'),
            ]);
        }

        return DB::transaction(function () use ($branchId, $lines): array {
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
                ->whereIn('id', array_keys($lines))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($lines as $itemId => $line) {
                $item = $inventoryItems->get($itemId);

                if (! $item) {
                    throw ValidationException::withMessages([
                        'data.items' => __('One or more selected products are not available.'),
                    ]);
                }

                if ($item->quantity < $line['quantity']) {
                    throw ValidationException::withMessages([
                        'data.items' => __('Only :count unit(s) of :product available in warehouse inventory.', [
                            'count' => $item->quantity,
                            'product' => $item->name,
                        ]),
                    ]);
                }
            }

            $transferred = [];

            foreach ($lines as $itemId => $line) {
                $item = $inventoryItems->get($itemId);
                $quantity = $line['quantity'];
                $expirationDate = $line['expiration_date']
                    ?? ($item->expiration_date?->toDateString());

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
                        'expiration_date' => $expirationDate,
                    ]);
                }

                $item->decrement('quantity', $quantity);
                $branchInventory->increment('quantity', $quantity);
                $branchInventory->forceFill([
                    'expiration_date' => self::earliestDate(
                        $branchInventory->expiration_date?->toDateString(),
                        $expirationDate,
                    ),
                ])->save();

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
     * @param  array<int, array{pos_inventory_item_id: mixed, quantity: mixed, expiration_date?: mixed}>  $items
     * @return array<int, array{quantity: int, expiration_date: ?string}>
     */
    private static function aggregateLines(array $items): array
    {
        $lines = [];

        foreach ($items as $line) {
            $itemId = (int) ($line['pos_inventory_item_id'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);

            if ($itemId < 1 || $quantity < 1) {
                continue;
            }

            $expirationDate = self::normalizeDate($line['expiration_date'] ?? null);

            if (! isset($lines[$itemId])) {
                $lines[$itemId] = [
                    'quantity' => $quantity,
                    'expiration_date' => $expirationDate,
                ];

                continue;
            }

            $lines[$itemId]['quantity'] += $quantity;
            $lines[$itemId]['expiration_date'] = self::earliestDate(
                $lines[$itemId]['expiration_date'],
                $expirationDate,
            );
        }

        return $lines;
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function earliestDate(?string $left, ?string $right): ?string
    {
        if ($left === null) {
            return $right;
        }

        if ($right === null) {
            return $left;
        }

        return $left <= $right ? $left : $right;
    }
}
