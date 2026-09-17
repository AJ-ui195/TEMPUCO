<?php

namespace App\Filament\Canteen\Pages;

use App\Enums\PosSaleChannel;
use App\Models\PosCanteenInventoryItem;
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

    public function content(Schema $schema): Schema
    {
        $totalItems = PosCanteenInventoryItem::query()->count();
        $activeItems = PosCanteenInventoryItem::query()->active()->count();
        $fastMovingReport = new FastMovingItemsReport(saleChannel: PosSaleChannel::Canteen);
        $fastMovingItems = $fastMovingReport->top(10);

        return $schema
            ->components([
                Section::make(__('Inventory overview'))
                    ->description(__('Manage canteen product stock from the sidebar. Total: :total · Active: :active', [
                        'total' => $totalItems,
                        'active' => $activeItems,
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
            ]);
    }
}
