<?php

namespace App\Filament\Cashier\Pages;

use App\Enums\PosSaleChannel;
use App\Filament\Cashier\Concerns\ManagesPosCheckout;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class PosGroceryPage extends BaseDashboard
{
    use ManagesPosCheckout;

    protected static ?string $title = 'Grocery POS';

    protected static ?string $navigationLabel = 'Grocery POS';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected string $view = 'filament.cashier.pos-grocery';

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Grocery POS');
    }

    public function getWidgets(): array
    {
        return [];
    }

    protected function getSaleChannel(): PosSaleChannel
    {
        return PosSaleChannel::Grocery;
    }

    
}
