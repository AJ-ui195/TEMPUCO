<?php

namespace App\Enums;

enum ModeOfPayment: string
{
    case CashPayment = 'cash_payment';
    case OverTheCounter = 'over_the_counter';

    public function getLabel(): string
    {
        return match ($this) {
            self::CashPayment => __('Cash Payment'),
            self::OverTheCounter => __('Over the Counter'),
        };
    }
}
