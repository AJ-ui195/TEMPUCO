<?php

namespace App\Filament\Widgets;

use App\Enums\PosSaleChannel;
use App\Support\PosSalesReport;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PosAnalyticsOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'POS sales';

    public string $period = PosSalesReport::PERIOD_TODAY;

    public int $year;

    public int $month;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    public function updatedPeriod(): void
    {
        $this->cachedStats = null;
    }

    public function updatedYear(): void
    {
        $this->cachedStats = null;
    }

    public function updatedMonth(): void
    {
        $this->cachedStats = null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Period'))
                    ->schema([
                        ToggleButtons::make('period')
                            ->hiddenLabel()
                            ->options([
                                PosSalesReport::PERIOD_TODAY => __('Today'),
                                PosSalesReport::PERIOD_MONTH => __('Per month'),
                                PosSalesReport::PERIOD_YEAR => __('Per year'),
                            ])
                            ->inline()
                            ->grouped()
                            ->live()
                            ->columnSpanFull(),
                        Select::make('year')
                            ->label(__('Year'))
                            ->options(
                                collect(range(now()->year, now()->year - 5))
                                    ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                                    ->all(),
                            )
                            ->live()
                            ->visible(fn (): bool => in_array($this->period, [
                                PosSalesReport::PERIOD_MONTH,
                                PosSalesReport::PERIOD_YEAR,
                            ], true)),
                        Select::make('month')
                            ->label(__('Month'))
                            ->options(
                                collect(range(1, 12))
                                    ->mapWithKeys(fn (int $month): array => [
                                        $month => Carbon::createFromDate($this->year, $month, 1)->format('F'),
                                    ])
                                    ->all(),
                            )
                            ->live()
                            ->visible(fn (): bool => $this->period === PosSalesReport::PERIOD_MONTH),
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ]),
                $this->getSectionContentComponent(),
            ]);
    }

    protected function getDescription(): ?string
    {
        return $this->report()->periodLabel();
    }

    protected function getStats(): array
    {
        $report = $this->report();
        $periodLabel = $report->periodLabel();

        $grocerySales = $this->cashSalesTotalForChannel($report, PosSaleChannel::Grocery);
        $canteenSales = $this->cashSalesTotalForChannel($report, PosSaleChannel::Canteen);
        $groceryCount = $this->cashTransactionCountForChannel($report, PosSaleChannel::Grocery);
        $canteenCount = $this->cashTransactionCountForChannel($report, PosSaleChannel::Canteen);

        return [
            Stat::make(__('Grocery POS'), number_format($grocerySales, 2))
                ->description(trans_choice(
                    ':count cash sale|:count cash sales',
                    $groceryCount,
                    ['count' => number_format($groceryCount)],
                ).' · '.$periodLabel)
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),

            Stat::make(__('Canteen POS'), number_format($canteenSales, 2))
                ->description(trans_choice(
                    ':count cash sale|:count cash sales',
                    $canteenCount,
                    ['count' => number_format($canteenCount)],
                ).' · '.$periodLabel)
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),
        ];
    }

    private function report(): PosSalesReport
    {
        return new PosSalesReport($this->period, $this->year, $this->month);
    }

    private function cashSalesTotalForChannel(PosSalesReport $report, PosSaleChannel $channel): float
    {
        return (float) (clone $report->cashSalesQuery())
            ->where('sale_channel', $channel->value)
            ->sum('total');
    }

    private function cashTransactionCountForChannel(PosSalesReport $report, PosSaleChannel $channel): int
    {
        return (clone $report->cashSalesQuery())
            ->where('sale_channel', $channel->value)
            ->count();
    }
}
