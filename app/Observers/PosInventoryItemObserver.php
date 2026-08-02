<?php

namespace App\Observers;

use App\Models\PosInventoryItem;
use App\Support\ExpirationNotifier;
use App\Support\LowStockNotifier;

class PosInventoryItemObserver
{
    public function created(PosInventoryItem $item): void
    {
        LowStockNotifier::notifyCatalogItemIfNewlyLow($item, null);
        ExpirationNotifier::notifyCatalogItemIfNewlyExpiring($item);
    }

    public function updated(PosInventoryItem $item): void
    {
        if ($item->wasChanged('quantity')) {
            $previous = $item->getOriginal('quantity');

            LowStockNotifier::notifyCatalogItemIfNewlyLow(
                $item,
                is_numeric($previous) ? (int) $previous : null,
            );
        }

        if ($item->wasChanged('expiration_date') || $item->wasChanged('quantity') || $item->wasChanged('is_active')) {
            ExpirationNotifier::notifyCatalogItemIfNewlyExpiring($item);
        }
    }
}
