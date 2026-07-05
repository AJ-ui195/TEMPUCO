<?php

namespace App\Filament\Canteen\Pages;

use App\Enums\PosSaleChannel;
use App\Filament\Cashier\Concerns\ManagesPosCheckout;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class PosCanteenPage extends BaseDashboard
{
    use ManagesPosCheckout;

    protected static ?string $title = 'Canteen POS';

    protected static ?string $navigationLabel = 'Canteen POS';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected string $view = 'filament.cashier.pos-grocery';

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Canteen POS');
    }

    public function getWidgets(): array
    {
        return [];
    }

    protected function getSaleChannel(): PosSaleChannel
    {
        return PosSaleChannel::Canteen;
    }

    

}
