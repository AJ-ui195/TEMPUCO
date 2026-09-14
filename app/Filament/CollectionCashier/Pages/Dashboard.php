<?php

namespace App\Filament\CollectionCashier\Pages;

use App\Filament\CollectionCashier\Widgets\CollectionOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static bool $isDiscovered = false;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    public function getWidgets(): array
    {
        return [
            CollectionOverviewWidget::class,
        ];
    }
}
