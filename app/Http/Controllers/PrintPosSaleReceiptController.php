<?php

namespace App\Http\Controllers;

use App\Models\PosSale;
use App\Support\PrintPosSaleReceipt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintPosSaleReceiptController extends Controller
{
    public function __invoke(Request $request, PosSale $sale): View
    {
        $user = $request->user();

        abort_unless(
            $user?->isCashier() || $user?->isCanteenCashier() || $user?->isAdmin(),
            403,
        );

        return view('filament.cashier.print-receipt', PrintPosSaleReceipt::viewData(
            $sale,
            $user,
            autoPrint: $request->boolean('auto'),
        ));
    }
}
