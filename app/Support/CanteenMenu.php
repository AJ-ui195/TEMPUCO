<?php

namespace App\Support;

use App\Models\PosCanteenInventoryItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The five fixed food choices shown on Canteen POS. Stored as catalog rows with
 * menu_slot 1–5 so sales still reference pos_canteen_inventory_items.
 */
final class CanteenMenu
{
    public const SLOT_COUNT = 5;

    /**
     * @return Collection<int, PosCanteenInventoryItem>
     */
    public static function choices(): Collection
    {
        self::ensureSlots();

        return PosCanteenInventoryItem::query()
            ->whereNotNull('menu_slot')
            ->whereBetween('menu_slot', [1, self::SLOT_COUNT])
            ->orderBy('menu_slot')
            ->get();
    }

    public static function ensureSlots(): void
    {
        $existing = PosCanteenInventoryItem::query()
            ->whereNotNull('menu_slot')
            ->pluck('menu_slot')
            ->map(fn ($slot): int => (int) $slot)
            ->all();

        for ($slot = 1; $slot <= self::SLOT_COUNT; $slot++) {
            if (in_array($slot, $existing, true)) {
                continue;
            }

            PosCanteenInventoryItem::query()->create([
                'name' => __('Menu item :n', ['n' => $slot]),
                'sku' => 'CANTEEN-MENU-'.$slot,
                'quantity' => 0,
                'unit_price' => 0,
                'cost' => 0,
                'is_active' => true,
                'menu_slot' => $slot,
            ]);
        }
    }

    /**
     * @param  array<int, array{name: string, unit_price: float|string, quantity?: int|string}>  $slots  keyed by menu_slot 1–5
     */
    public static function save(array $slots): void
    {
        self::ensureSlots();

        DB::transaction(function () use ($slots): void {
            foreach (range(1, self::SLOT_COUNT) as $slot) {
                $payload = $slots[$slot] ?? null;

                if (! is_array($payload)) {
                    throw new InvalidArgumentException(__('Missing menu choice :n.', ['n' => $slot]));
                }

                $name = trim((string) ($payload['name'] ?? ''));
                $price = round((float) str_replace(',', '', (string) ($payload['unit_price'] ?? 0)), 2);
                $quantity = (int) ($payload['quantity'] ?? 0);

                if ($name === '') {
                    throw new InvalidArgumentException(__('Enter a name for menu choice :n.', ['n' => $slot]));
                }

                if ($price < 0) {
                    throw new InvalidArgumentException(__('Price for menu choice :n cannot be negative.', ['n' => $slot]));
                }

                if ($quantity < 0) {
                    throw new InvalidArgumentException(__('Quantity for menu choice :n cannot be negative.', ['n' => $slot]));
                }

                PosCanteenInventoryItem::query()
                    ->where('menu_slot', $slot)
                    ->update([
                        'name' => $name,
                        'unit_price' => $price,
                        'quantity' => $quantity,
                        'is_active' => true,
                    ]);
            }
        });
    }
}
