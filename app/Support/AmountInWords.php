<?php

namespace App\Support;

class AmountInWords
{
    /**
     * English words for a PHP amount (pesos, two decimal places).
     */
    public static function format(float|int|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $value = round((float) $amount, 2);
        $pesos = (int) floor($value);
        $centavos = (int) round(($value - $pesos) * 100);

        if (! extension_loaded('intl')) {
            return number_format($value, 2).' PHP';
        }

        $spellout = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $pesoWords = ucfirst((string) $spellout->format($pesos));

        if ($centavos === 0) {
            return "{$pesoWords} pesos";
        }

        $centWords = (string) $spellout->format($centavos);

        return "{$pesoWords} pesos and {$centWords} centavo".($centavos === 1 ? '' : 's');
    }
}
