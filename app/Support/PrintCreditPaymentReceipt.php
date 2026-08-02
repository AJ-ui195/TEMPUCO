<?php

namespace App\Support;

use App\Models\PosCreditPayment;
use App\Models\User;

final class PrintCreditPaymentReceipt
{
    public static function printUrl(PosCreditPayment $payment, bool $autoPrint = true): string
    {
        return route('pos.credit-payments.print-receipt', [
            'payment' => $payment,
            'auto' => $autoPrint ? 1 : 0,
        ]);
    }

    /**
     * @return array{
     *     payment: PosCreditPayment,
     *     cashier: ?User,
     *     balanceBefore: float,
     *     balanceAfter: float,
     *     autoPrint: bool
     * }
     */
    public static function viewData(PosCreditPayment $payment, ?User $cashier = null, bool $autoPrint = false): array
    {
        $payment->loadMissing(['member', 'cashier']);

        $balanceAfter = $payment->member instanceof User
            ? (new MemberPosCredit($payment->member))->outstandingAfter($payment)
            : 0.0;

        return [
            'payment' => $payment,
            'cashier' => $cashier ?? $payment->cashier,
            'balanceBefore' => round($balanceAfter + (float) $payment->amount, 2),
            'balanceAfter' => $balanceAfter,
            'autoPrint' => $autoPrint,
        ];
    }
}
