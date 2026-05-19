<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\LoanResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLoans extends ListRecords
{
    protected static string $resource = LoanResource::class;

    protected static ?string $title = 'Loan applications';

    public function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with('user');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
