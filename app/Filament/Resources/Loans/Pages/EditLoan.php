<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\LoanResource;
use Filament\Resources\Pages\EditRecord;

class EditLoan extends EditRecord
{
    protected static string $resource = LoanResource::class;

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
        unset($data['status'], $data['approved_at'], $data['rejected_at'], $data['user_id'], $data['decided_by']);

        return $data;
    }
}
