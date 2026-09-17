<?php

namespace App\Filament\Canteen\Pages;

use App\Enums\PosSaleChannel;
use App\Filament\Cashier\Concerns\ManagesPosCheckout;
use App\Models\PosCanteenInventoryItem;
use App\Support\CanteenMenu;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class PosCanteenPage extends BaseDashboard
{
    use ManagesPosCheckout;

    protected static ?string $title = 'Canteen POS';

    protected static ?string $navigationLabel = 'Canteen POS';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected string $view = 'filament.canteen.pos-canteen';

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Canteen POS');
    }

    public function getWidgets(): array
    {
        return [];
    }

    /**
     * @return Collection<int, PosCanteenInventoryItem>
     */
    public function getMenuChoices(): Collection
    {
        return CanteenMenu::choices();
    }

    public function addMenuChoice(int $productId): void
    {
        $product = PosCanteenInventoryItem::query()
            ->whereKey($productId)
            ->whereNotNull('menu_slot')
            ->first();

        if (! $product instanceof PosCanteenInventoryItem) {
            $this->setScanFeedback(__('That menu choice is not available.'), true);

            return;
        }

        $this->addCatalogProduct($product);
    }

    protected function getSaleChannel(): PosSaleChannel
    {
        return PosSaleChannel::Canteen;
    }
}
