<?php

namespace App\Observers;

use App\Models\PosCanteenInventoryItem;
use App\Support\CanteenLowStockNotifier;

class PosCanteenInventoryItemObserver
{
    public function created(PosCanteenInventoryItem $item): void
    {
        CanteenLowStockNotifier::notifyCatalogItemIfNewlyLow($item, null);
    }

    public function updated(PosCanteenInventoryItem $item): void
    {
        if (! $item->wasChanged('quantity')) {
            return;
        }

        $previous = $item->getOriginal('quantity');

        CanteenLowStockNotifier::notifyCatalogItemIfNewlyLow(
            $item,
            is_numeric($previous) ? (int) $previous : null,
        );
    }
}
