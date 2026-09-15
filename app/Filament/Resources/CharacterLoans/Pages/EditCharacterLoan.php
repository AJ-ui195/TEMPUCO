<?php

namespace App\Filament\Resources\CharacterLoans\Pages;

use App\Filament\Resources\CharacterLoans\CharacterLoanResource;
use App\Filament\Resources\Loans\Concerns\PlacesLoanFormActionsInSchema;
use Filament\Resources\Pages\EditRecord;

class EditCharacterLoan extends EditRecord
{
    use PlacesLoanFormActionsInSchema;

    protected static string $resource = CharacterLoanResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->certification()->firstOrCreate([]);
        $this->record->committeeDecision()->firstOrCreate([]);
    }
}
