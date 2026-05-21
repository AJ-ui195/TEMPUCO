<?php

namespace App\Filament\User\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Credits extends Page
{
    protected static ?string $navigationLabel = 'Credits';

    protected static ?string $title = 'Credits';

    protected static ?string $slug = 'credits';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Credits');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Credits'))
                    ->description(__('View your credit balance and transaction history.')),
            ]);
    }
}
