<?php

namespace App\Filament\Cashier\Pages;

use App\Enums\PosSaleChannel;
use App\Models\PosInventoryItem;
use App\Support\BirDailySalesExcelExporter;
use App\Support\BirDailySalesReport;
use App\Support\CloseInventoryExcelExporter;
use App\Support\ExpirationNotifier;
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
        ExpirationNotifier::notifyDashboardSummary();
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
        $expiringItems = ExpirationNotifier::expiringCatalogItems();
        $expiringBranchItems = ExpirationNotifier::expiringBranchItems();
        $expiringCount = $expiringItems->count() + $expiringBranchItems->count();
        $fastMovingReport = new FastMovingItemsReport(saleChannel: PosSaleChannel::Grocery);
        $fastMovingItems = $fastMovingReport->top(10);

        $lowStockList = $lowStockItems->isEmpty()
            ? __('No products are below their reorder level.')
            : $this->formatLowStockList($lowStockItems);

        $expiringList = $expiringCount === 0
            ? __('No products expire within the next 2 weeks.')
            : $this->formatExpiringList($expiringItems, $expiringBranchItems);

        return $schema
            ->components([
                Section::make(__('Inventory overview'))
                    ->description(__('Manage grocery product stock from the sidebar. Total: :total · Active: :active · Low stock: :low · Expiring in 2 weeks: :expiring', [
                        'total' => $totalItems,
                        'active' => $activeItems,
                        'low' => $lowStockCount,
                        'expiring' => $expiringCount,
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
                Section::make(__('Expiring within 2 weeks'))
                    ->description($expiringCount > 0
                        ? __('These products expire today or within the next 14 days.')
                        : __('No warehouse or branch stock is within 2 weeks of expiration.'))
                    ->schema([
                        TextEntry::make('expiring_list')
                            ->hiddenLabel()
                            ->state(fn (): HtmlString => new HtmlString(
                                '<div style="font-size: 0.875rem; line-height: 1.6;">'.$expiringList.'</div>'
                            ))
                            ->columnSpanFull(),
                    ])
                    ->visible($expiringCount > 0 || $totalItems > 0)
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

    /**
     * @param  \Illuminate\Support\Collection<int, PosInventoryItem>  $catalog
     * @param  \Illuminate\Support\Collection<int, \App\Models\PosBranchInventory>  $branch
     */
    protected function formatExpiringList(\Illuminate\Support\Collection $catalog, \Illuminate\Support\Collection $branch): string
    {
        $parts = [];

        foreach ($catalog as $item) {
            $parts[] = __(':name (warehouse) — :qty · expires :date', [
                'name' => $item->name,
                'qty' => $item->quantity,
                'date' => $item->expiration_date?->format('M j, Y') ?? '—',
            ]);
        }

        foreach ($branch as $row) {
            $parts[] = __(':name (:branch) — :qty · expires :date', [
                'name' => $row->inventoryItem?->name ?? __('Unknown'),
                'branch' => $row->branch?->name ?? __('Branch'),
                'qty' => $row->quantity,
                'date' => $row->expiration_date?->format('M j, Y') ?? '—',
            ]);
        }

        return implode('<br>', $parts);
    }
}
