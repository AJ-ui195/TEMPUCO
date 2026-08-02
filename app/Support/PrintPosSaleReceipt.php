<?php

namespace App\Support;

use App\Models\PosSale;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PrintPosSaleReceipt
{
    public static function printUrl(PosSale $sale, bool $autoPrint = true): string
    {
        return route('pos.sales.print-receipt', [
            'sale' => $sale,
            'auto' => $autoPrint ? 1 : 0,
        ]);
    }

    /**
     * @return array{sale: PosSale, cashier: ?User, autoPrint: bool}
     */
    public static function viewData(PosSale $sale, ?User $cashier, bool $autoPrint = false): array
    {
        $sale->load([
            'items' => fn (HasMany $items) => $items->notVoided(),
            'items.inventoryItem',
            'items.canteenInventoryItem',
        ]);

        $sale->loadMissing(['member', 'cashier']);

        return [
            'sale' => $sale,
            'cashier' => $cashier,
            'autoPrint' => $autoPrint,
        ];
    }
}
