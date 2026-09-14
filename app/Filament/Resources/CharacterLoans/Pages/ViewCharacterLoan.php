<?php

namespace App\Filament\Resources\CharacterLoans\Pages;

use App\Filament\Resources\CharacterLoans\CharacterLoanResource;
use App\Filament\Resources\Loans\Concerns\RecordsMemberLoanPayment;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCharacterLoan extends ViewRecord
{
    use RecordsMemberLoanPayment;

    protected static string $resource = CharacterLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->recordPaymentAction(),
            EditAction::make(),
        ];
    }
}
