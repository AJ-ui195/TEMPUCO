<?php

namespace App\Support;

use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\Exceptions\UnknownTypeException;

final class ProductBarcodeRenderer
{
    public static function htmlWithLabel(string $barcode, float $height = 72): string
    {
        $digits = preg_replace('/\D/', '', $barcode) ?? '';

        if ($digits === '') {
            return '';
        }

        try {
            $generator = new BarcodeGeneratorSVG;
            $svg = $generator->getBarcode($digits, $generator::TYPE_CODE_128, 2, $height);
        } catch (UnknownTypeException) {
            return '';
        }

        return sprintf(
            '<div style="display:inline-flex;flex-direction:column;align-items:center;padding:0.75rem 1rem;border:1px solid rgba(148,163,184,0.35);border-radius:0.5rem;background:#fff;">%s<div style="margin-top:0.35rem;font-family:ui-monospace,monospace;font-size:0.875rem;letter-spacing:0.12em;color:#111;">%s</div></div>',
            $svg,
            e($digits),
        );
    }

    public static function formatUpcLabel(string $barcode): string
    {
        return self::formatLabel(preg_replace('/\D/', '', $barcode) ?? '');
    }

    public static function formatLabel(string $digits): string
    {
        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 12) {
            return sprintf(
                '%s %s %s %s',
                $digits[0],
                substr($digits, 1, 5),
                substr($digits, 6, 5),
                $digits[11],
            );
        }

        if (strlen($digits) === 13) {
            return sprintf(
                '%s %s %s %s',
                substr($digits, 0, 1),
                substr($digits, 1, 6),
                substr($digits, 7, 5),
                $digits[12],
            );
        }

        return $digits;
    }
}
