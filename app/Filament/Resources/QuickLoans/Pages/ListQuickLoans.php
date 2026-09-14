<?php

namespace App\Filament\Resources\QuickLoans\Pages;

use App\Filament\Resources\QuickLoans\QuickLoanResource;
use Filament\Resources\Pages\ListRecords;

class ListQuickLoans extends ListRecords
{
    protected static string $resource = QuickLoanResource::class;

    protected static ?string $title = 'Quick loans';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
