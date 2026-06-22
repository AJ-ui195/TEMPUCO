<?php

namespace App\Filament\Canteen\Pages;

use App\Enums\PosSaleChannel;
use App\Models\PosCanteenInventoryItem;
use App\Support\CanteenLowStockNotifier;
use App\Support\FastMovingItemsReport;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class CanteenInventoryPage extends Page
{
    protected static ?string $navigationLabel = 'Overview';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'inventory';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $title = 'Canteen inventory';

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Canteen inventory');
    }

    public function mount(): void
    {
        CanteenLowStockNotifier::notifyDashboardSummary();
    }

    public function content(Schema $schema): Schema
    {
        $totalItems = PosCanteenInventoryItem::query()->count();
        $activeItems = PosCanteenInventoryItem::query()->active()->count();
        $lowStockItems = CanteenLowStockNotifier::lowStockCatalogItems();
        $lowStockCount = $lowStockItems->count();
        $fastMovingReport = new FastMovingItemsReport(saleChannel: PosSaleChannel::Canteen);
        $fastMovingItems = $fastMovingReport->top(10);

        $lowStockList = $lowStockItems->isEmpty()
            ? __('No products are below their reorder level.')
            : $lowStockItems
                ->map(fn (PosCanteenInventoryItem $item): string => __(':name — :qty in stock (reorder at :level)', [
                    'name' => $item->name,
                    'qty' => $item->quantity,
                    'level' => $item->reorder_level,
                ]))
                ->implode('<br>');

        return $schema
            ->components([
                Section::make(__('Inventory overview'))
                    ->description(__('Manage canteen product stock from the sidebar. Total: :total · Active: :active · Low stock: :low', [
                        'total' => $totalItems,
                        'active' => $activeItems,
                        'low' => $lowStockCount,
                    ])),
                Section::make(__('Top 10 fast moving items'))
                    ->description(__('Products with the highest quantity sold — :period.', [
                        'period' => $fastMovingReport->periodLabel(),
                    ]))
                    ->schema([
                        TextEntry::make('fast_moving_table')
                            ->hiddenLabel()
                            ->state(fn (): HtmlString => new HtmlString(
                                view('filament.pos.fast-moving-items-table', [
                                    'items' => $fastMovingItems,
                                ])->render()
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make(__('Low stock alerts'))
                    ->description($lowStockCount > 0
                        ? __('These products are at or below their reorder level.')
                        : __('All catalog products with a reorder level are adequately stocked.'))
                    ->schema([
                        TextEntry::make('low_stock_list')
                            ->hiddenLabel()
                            ->state(fn (): HtmlString => new HtmlString(
                                '<div style="font-size: 0.875rem; line-height: 1.6;">'.$lowStockList.'</div>'
                            ))
                            ->columnSpanFull(),
                    ])
                    ->visible($lowStockCount > 0 || $totalItems > 0),
            ]);
    }
}
