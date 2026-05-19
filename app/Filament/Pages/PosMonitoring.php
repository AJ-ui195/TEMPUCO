<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
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
                Section::make(__('POS overview'))
                    ->description(__('Monitor point-of-sale activity, terminals, and transactions from this dashboard.')),
            ]);
    }
}
