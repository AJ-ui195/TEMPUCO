<?php

namespace App\Filament\Resources\QuickLoans\Pages;

use App\Filament\Resources\Loans\Concerns\PlacesLoanFormActionsInSchema;
use App\Filament\Resources\QuickLoans\QuickLoanResource;
use Filament\Resources\Pages\EditRecord;

class EditQuickLoan extends EditRecord
{
    use PlacesLoanFormActionsInSchema;

    protected static string $resource = QuickLoanResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->certification()->firstOrCreate([]);
        $this->record->committeeDecision()->firstOrCreate([]);
    }
}
