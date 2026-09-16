<?php

namespace App\Support;

use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

final class PesoInput
{
    public static function decorate(TextInput $input): TextInput
    {
        return $input
            ->prefix('₱')
            ->inputMode('decimal')
            ->stripCharacters(',')
            ->mask(RawJs::make('$money($input, \'.\', \',\', 2)'));
    }

    public static function parse(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        $normalized = str_replace([',', ' '], '', trim((string) $value));

        if ($normalized === '' || ! is_numeric($normalized)) {
            return 0.0;
        }

        return round((float) $normalized, 2);
    }

    public static function format(float $amount): string
    {
        if ($amount <= 0) {
            return '';
        }

        return number_format($amount, 2, '.', ',');
    }
}
