<?php

namespace App\Enums;

enum PosSaleChannel: string
{
    case Grocery = 'grocery';
    case Canteen = 'canteen';

    public function getLabel(): string
    {
        return match ($this) {
            self::Grocery => __('Grocery'),
            self::Canteen => __('Canteen'),
        };
    }
}
