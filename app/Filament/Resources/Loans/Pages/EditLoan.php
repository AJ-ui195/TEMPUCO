<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\Concerns\PlacesLoanFormActionsInSchema;
use App\Filament\Resources\Loans\LoanResource;
use Filament\Resources\Pages\EditRecord;

class EditLoan extends EditRecord
{
    use PlacesLoanFormActionsInSchema;

    protected static string $resource = LoanResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->certification()->firstOrCreate([]);
        $this->record->committeeDecision()->firstOrCreate([]);
    }
}
