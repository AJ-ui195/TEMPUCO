<?php

namespace App\Filament\Resources\CharacterLoans\Pages;

use App\Filament\Resources\CharacterLoans\CharacterLoanResource;
use App\Filament\Resources\Loans\Concerns\DecidesMemberLoan;
use App\Filament\Resources\Loans\Concerns\RecordsMemberLoanPayment;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCharacterLoan extends ViewRecord
{
    use DecidesMemberLoan;
    use RecordsMemberLoanPayment;

    protected static string $resource = CharacterLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->decisionActions(),
            ...$this->recordPaymentAction(),
            EditAction::make(),
        ];
    }
}
