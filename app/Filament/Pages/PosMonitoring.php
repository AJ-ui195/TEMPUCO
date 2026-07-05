<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PosAnalyticsOverviewWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class PosMonitoring extends Page
{
    protected static ?string $navigationLabel = 'POS Monitoring';

    protected static ?string $title = 'POS Monitoring';

    protected static ?string $slug = 'pos-monitoring';

    protected static ?int $navigationSort = 30;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('POS Monitoring');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents([
                        PosAnalyticsOverviewWidget::class,
                    ])),
            ]);
    }
}
