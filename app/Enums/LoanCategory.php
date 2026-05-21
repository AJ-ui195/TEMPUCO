<?php

namespace App\Enums;

enum LoanCategory: string
{
    case Restructure = 'restructure';
    case Renewal = 'renewal';
    case AdditionalNew = 'additional_new';

    public function getLabel(): string
    {
        return match ($this) {
            self::Restructure => __('Restructure'),
            self::Renewal => __('Renewal'),
            self::AdditionalNew => __('Additional / New'),
        };
    }
}
