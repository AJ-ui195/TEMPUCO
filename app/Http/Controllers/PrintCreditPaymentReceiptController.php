<?php

namespace App\Http\Controllers;

use App\Models\PosCreditPayment;
use App\Support\PrintCreditPaymentReceipt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintCreditPaymentReceiptController extends Controller
{
    public function __invoke(Request $request, PosCreditPayment $payment): View
    {
        $user = $request->user();

        abort_unless(
            $user?->isCashier() || $user?->isCanteenCashier() || $user?->isAdmin(),
            403,
        );

        return view('filament.cashier.print-credit-payment-receipt', PrintCreditPaymentReceipt::viewData(
            $payment,
            autoPrint: $request->boolean('auto'),
        ));
    }
}
