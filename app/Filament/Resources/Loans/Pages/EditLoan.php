<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Enums\LoanStatus;
use App\Filament\Resources\Loans\LoanResource;
use Filament\Resources\Pages\EditRecord;

class EditLoan extends EditRecord
{
    protected static string $resource = LoanResource::class;

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
