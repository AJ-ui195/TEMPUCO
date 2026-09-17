<?php

namespace App\Support;

use App\Models\PosCanteenInventoryItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * Canteen menu items no longer track reorder levels. Kept as no-ops so existing
 * observers and pages keep working without low-stock alerts.
 */
final class CanteenLowStockNotifier
{
    public static function notifyCatalogItemIfNewlyLow(PosCanteenInventoryItem $item, ?int $previousQuantity): void
    {
        //
    }

    public static function notifyDashboardSummary(): void
    {
        //
    }

    /**
     * @return Collection<int, PosCanteenInventoryItem>
     */
    public static function lowStockCatalogItems(): Collection
    {
        return new Collection;
    }
}
