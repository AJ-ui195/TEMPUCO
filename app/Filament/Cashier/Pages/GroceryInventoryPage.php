<?php

namespace App\Filament\Cashier\Pages;

use App\Enums\PosSaleChannel;
use App\Models\PosInventoryItem;
use App\Support\BirDailySalesExcelExporter;
use App\Support\BirDailySalesReport;
use App\Support\CloseInventoryExcelExporter;
use App\Support\FastMovingItemsReport;
use App\Support\LowStockNotifier;
use App\Support\PhilippineTime;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GroceryInventoryPage extends Page
{
    protected static ?string $navigationLabel = 'Overview';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'inventory';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $title = 'Grocery inventory';

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Grocery inventory');
    }

    public function mount(): void
    {
        LowStockNotifier::notifyDashboardSummary();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        $now = PhilippineTime::now();

        return [
            Action::make('exportCloseInventoryReport')
                ->label(__('Export close inventory report'))
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->action(fn (): StreamedResponse => (new CloseInventoryExcelExporter)->download()),
            Action::make('exportBirDailySales')
                ->label(__('Export BIR daily sales'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->form([
                    DatePicker::make('from')
                        ->label(__('From'))
                        ->default($now->copy()->startOfMonth()->toDateString())
                        ->required()
                        ->native(false),
                    DatePicker::make('to')
                        ->label(__('To'))
                        ->default($now->copy()->endOfMonth()->toDateString())
                        ->required()
                        ->native(false)
                        ->afterOrEqual('from'),
                ])
                ->action(function (array $data): StreamedResponse {
                    $report = new BirDailySalesReport(
                        from: $data['from'],
                        to: $data['to'],
                        saleChannel: PosSaleChannel::Grocery,
                    );

                    return (new BirDailySalesExcelExporter($report))->download();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $totalItems = PosInventoryItem::query()->count();
        $activeItems = PosInventoryItem::query()->active()->count();
        $lowStockItems = LowStockNotifier::lowStockCatalogItems();
        $lowStockCount = $lowStockItems->count();
        $fastMovingReport = new FastMovingItemsReport(saleChannel: PosSaleChannel::Grocery);
        $fastMovingItems = $fastMovingReport->top(10);

        $lowStockList = $lowStockItems->isEmpty()
            ? __('No products are below their reorder level.')
            : $this->formatLowStockList($lowStockItems);

        return $schema
            ->components([
                Section::make(__('Inventory overview'))
                    ->description(__('Manage grocery product stock from the sidebar. Total: :total · Active: :active · Low stock: :low', [
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

    /**
     * @param  \Illuminate\Support\Collection<int, PosInventoryItem>  $items
     */
    protected function formatLowStockList(\Illuminate\Support\Collection $items): string
    {
        $parts = [];

        foreach ($items as $item) {
            $parts[] = __(':name — :qty in stock (reorder at :level)', [
                'name' => $item->name,
                'qty' => $item->quantity,
                'level' => $item->reorder_level,
            ]);
        }

        return implode('<br>', $parts);
    }
}
