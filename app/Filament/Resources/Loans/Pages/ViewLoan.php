<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\Concerns\RecordsMemberLoanPayment;
use App\Filament\Resources\Loans\LoanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLoan extends ViewRecord
{
    use RecordsMemberLoanPayment;

    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->recordPaymentAction(),
            EditAction::make(),
        ];
    }
}
