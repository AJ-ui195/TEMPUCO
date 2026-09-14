<?php

namespace App\Filament\Resources\CharacterLoans\Pages;

use App\Filament\Resources\CharacterLoans\CharacterLoanResource;
use Filament\Resources\Pages\ListRecords;

class ListCharacterLoans extends ListRecords
{
    protected static string $resource = CharacterLoanResource::class;

    protected static ?string $title = 'Character / emergency loans';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
