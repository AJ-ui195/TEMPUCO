<?php

namespace App\Filament\Pos\Pages;

use App\Models\PosInventoryItem;
use App\Support\FastMovingItemsReport;
use App\Support\LowStockNotifier;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class InventoryDashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    public function mount(): void
    {
        LowStockNotifier::notifyDashboardSummary();
    }

    #[\Override]
    public function getWidgets(): array
    {
        return Filament::getWidgets();
    }

    #[\Override]
    public function content(Schema $schema): Schema
    {
        $totalItems = PosInventoryItem::query()->count();
        $activeItems = PosInventoryItem::query()->active()->count();
        $lowStockItems = LowStockNotifier::lowStockCatalogItems();
        $lowStockCount = $lowStockItems->count();
        $fastMovingReport = new FastMovingItemsReport;
        $fastMovingItems = $fastMovingReport->top(10);

        $lowStockList = $lowStockItems->isEmpty()
            ? __('No products are below their reorder level.')
            : $lowStockItems
                ->map(fn (PosInventoryItem $item): string => __(':name — :qty in stock (reorder at :level)', [
                    'name' => $item->name,
                    'qty' => $item->quantity,
                    'level' => $item->reorder_level,
                ]))
                ->implode('<br>');

        return $schema
            ->components([
                Section::make(__('Welcome'))
                    ->description(__('You are signed in to the inventory portal. Use the sidebar to manage products, suppliers, and branches.')),
                Section::make(__('Inventory overview'))
                    ->description(__('Total: :total · Active: :active · Low stock: :low', [
                        'total' => $totalItems,
                        'active' => $activeItems,
                        'low' => $lowStockCount,
                    ])),
                $this->getWidgetsContentComponent(),
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
