<?php

namespace App\Observers;

use App\Models\PosBranchInventory;
use App\Support\ExpirationNotifier;
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

        ExpirationNotifier::notifyBranchItemIfNewlyExpiring($record);
    }

    public function updated(PosBranchInventory $record): void
    {
        $record->loadMissing(['inventoryItem', 'branch']);

        if (! $record->inventoryItem || ! $record->branch) {
            return;
        }

        if ($record->wasChanged('quantity')) {
            $previous = $record->getOriginal('quantity');

            LowStockNotifier::notifyBranchItemIfNewlyLow(
                $record->inventoryItem,
                $record->quantity,
                is_numeric($previous) ? (int) $previous : null,
                $record->branch->name,
            );
        }

        if ($record->wasChanged('expiration_date') || $record->wasChanged('quantity')) {
            ExpirationNotifier::notifyBranchItemIfNewlyExpiring($record);
        }
    }
}
