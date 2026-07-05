<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;

trait ConfiguresInventoryProductBarcode
{
    /**
     * @return array<int, TextInput|ViewField>
     */
    protected static function inventoryBarcodeFormFields(): array
    {
        return [
            TextInput::make('sku')
                ->label(__('SKU'))
                ->maxLength(64)
                ->unique(ignoreRecord: true)
                ->live(onBlur: true)
                ->helperText(__('Product code or numeric barcode for POS scanning.')),
            ViewField::make('barcode_preview')
                ->label(__('Barcode preview'))
                ->view('filament.components.product-barcode-preview')
                ->viewData(fn (callable $get): array => [
                    'barcode' => $get('sku'),
                    'productName' => $get('name'),
                ])
                ->visible(fn (callable $get): bool => filled($get('sku')) && ctype_digit((string) $get('sku')))
                ->columnSpanFull(),
        ];
    }
}
