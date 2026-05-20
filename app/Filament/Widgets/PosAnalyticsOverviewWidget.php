<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PosAnalyticsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected ?string $heading = 'POS analytics';

    protected function getStats(): array
    {
        // Replace with real POS aggregates when a sales/transaction model exists.
        $totalSalesThisMonth = 0.0;
        $todaySales = 0.0;
        $canteenTodaySales = 0.0;

        return [
            Stat::make(__('Total sales this month'), number_format($totalSalesThisMonth, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make(__('Today Sales'), number_format($todaySales, 2))
                ->description(now()->format('M j, Y'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),

            Stat::make(__('Canteen Today Sales'), number_format($canteenTodaySales, 2))
                ->description(__('Canteen point of sale'))
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),
        ];
    }
}
