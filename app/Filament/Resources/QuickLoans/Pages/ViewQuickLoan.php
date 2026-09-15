<?php

namespace App\Filament\Resources\QuickLoans\Pages;

use App\Filament\Resources\Loans\Concerns\DecidesMemberLoan;
use App\Filament\Resources\Loans\Concerns\RecordsMemberLoanPayment;
use App\Filament\Resources\QuickLoans\QuickLoanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuickLoan extends ViewRecord
{
    use DecidesMemberLoan;
    use RecordsMemberLoanPayment;

    protected static string $resource = QuickLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->decisionActions(),
            ...$this->recordPaymentAction(),
            EditAction::make(),
        ];
    }
}
