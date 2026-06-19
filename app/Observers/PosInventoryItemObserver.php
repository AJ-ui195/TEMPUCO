<?php

namespace App\Observers;

use App\Models\PosInventoryItem;
use App\Support\LowStockNotifier;

class PosInventoryItemObserver
{
    public function created(PosInventoryItem $item): void
    {
        LowStockNotifier::notifyCatalogItemIfNewlyLow($item, null);
    }

    public function updated(PosInventoryItem $item): void
    {
        if (! $item->wasChanged('quantity')) {
            return;
        }

        $previous = $item->getOriginal('quantity');

        LowStockNotifier::notifyCatalogItemIfNewlyLow(
            $item,
            is_numeric($previous) ? (int) $previous : null,
        );
    }
}
