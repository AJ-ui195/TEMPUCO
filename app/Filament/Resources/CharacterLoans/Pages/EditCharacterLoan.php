<?php

namespace App\Filament\Resources\CharacterLoans\Pages;

use App\Enums\LoanStatus;
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === LoanStatus::Approved->value && blank($data['approved_at'] ?? null)) {
            $data['approved_at'] = now();
        }

        if (($data['status'] ?? null) !== LoanStatus::Approved->value) {
            $data['approved_at'] = null;
        }

        return $data;
    }
}
