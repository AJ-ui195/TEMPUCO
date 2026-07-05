<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

final class ProductBarcodeGenerator
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function fromSku(string $sku, ?int $ignoreId = null, string $modelClass = \App\Models\PosInventoryItem::class): string
    {
        $barcode = self::buildUpcA($sku, $modelClass);

        return self::ensureUnique($barcode, $modelClass, $ignoreId);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function buildUpcA(string $sku, string $modelClass): string
    {
        $digits = preg_replace('/\D/', '', trim($sku)) ?? '';
        $sequence = (int) $modelClass::query()->max('id') + 1;

        if (strlen($digits) >= 11) {
            $payload = substr($digits, 0, 11);
        } elseif ($digits !== '') {
            $payload = '7'.str_pad(substr($digits, -10), 10, '0', STR_PAD_LEFT);
        } else {
            $payload = '7'.str_pad((string) $sequence, 10, '0', STR_PAD_LEFT);
        }

        $payload = str_pad(substr($payload, 0, 11), 11, '0', STR_PAD_LEFT);

        return $payload.self::upcCheckDigit($payload);
    }

    private static function upcCheckDigit(string $elevenDigits): int
    {
        $sum = 0;

        for ($index = 0; $index < 11; $index++) {
            $digit = (int) $elevenDigits[$index];
            $sum += ($index % 2 === 0) ? $digit * 3 : $digit;
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function ensureUnique(string $barcode, string $modelClass, ?int $ignoreId): string
    {
        $payload = substr($barcode, 0, 11);
        $attempt = 0;

        while ($attempt < 10_000) {
            $candidate = $payload.self::upcCheckDigit($payload);

            if (! self::exists($candidate, $modelClass, $ignoreId)) {
                return $candidate;
            }

            $numericBody = (int) substr($payload, 1) + 1;
            $payload = $payload[0].str_pad((string) $numericBody, 10, '0', STR_PAD_LEFT);
            $attempt++;
        }

        return $barcode;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function exists(string $barcode, string $modelClass, ?int $ignoreId): bool
    {
        return $modelClass::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('sku', $barcode)
            ->exists();
    }

}