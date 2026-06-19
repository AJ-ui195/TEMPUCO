<?php

namespace App\Observers;

use App\Models\PosBranchInventory;
use App\Support\LowStockNotifier;

class PosBranchInventoryObserver
{
    public function created(PosBranchInventory $record): void
    {
        $record->loadMissing(['inventoryItem', 'branch']);

        if (! $record->inventoryItem || ! $record->branch) {
            return;
        }

        LowStockNotifier::notifyBranchItemIfNewlyLow(
            $record->inventoryItem,
            $record->quantity,
            null,
            $record->branch->name,
        );
    }

    public function updated(PosBranchInventory $record): void
    {
        if (! $record->wasChanged('quantity')) {
            return;
        }

        $record->loadMissing(['inventoryItem', 'branch']);

        if (! $record->inventoryItem || ! $record->branch) {
            return;
        }

        $previous = $record->getOriginal('quantity');

        LowStockNotifier::notifyBranchItemIfNewlyLow(
            $record->inventoryItem,
            $record->quantity,
            is_numeric($previous) ? (int) $previous : null,
            $record->branch->name,
        );
    }
}
