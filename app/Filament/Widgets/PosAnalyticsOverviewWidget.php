<?php

namespace App\Filament\Widgets;

use App\Enums\PosSaleChannel;
use App\Support\PosSalesReport;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PosAnalyticsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected ?string $heading = 'POS analytics';

    protected function getStats(): array
    {
        $monthReport = new PosSalesReport(PosSalesReport::PERIOD_MONTH);
        $monthSummary = $monthReport->summary();

        $todayReport = new PosSalesReport(PosSalesReport::PERIOD_TODAY);
        $todaySummary = $todayReport->summary();

        $canteenTodaySales = (float) (clone $todayReport->cashSalesQuery())
            ->where('sale_channel', PosSaleChannel::Canteen->value)
            ->sum('total');

        return [
            Stat::make(__('Total sales this month'), number_format($monthSummary['total_revenue'], 2))
                ->description(trans_choice(
                    ':count cash sale|:count cash sales',
                    $monthSummary['cash_transaction_count'],
                    ['count' => number_format($monthSummary['cash_transaction_count'])],
                ).' · '.now()->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make(__('Today Sales'), number_format($todaySummary['total_revenue'], 2))
                ->description(trans_choice(
                    ':count cash sale|:count cash sales',
                    $todaySummary['cash_transaction_count'],
                    ['count' => number_format($todaySummary['cash_transaction_count'])],
                ).' · '.now()->format('M j, Y'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),

            Stat::make(__('Canteen Today Sales'), number_format($canteenTodaySales, 2))
                ->description(__('Canteen point of sale').' · '.now()->format('M j, Y'))
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),
        ];
    }
}
