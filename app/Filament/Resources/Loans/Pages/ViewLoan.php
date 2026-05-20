<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\LoanResource;
use App\Models\Loan;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLoan extends ViewRecord
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordPayment')
                ->label(__('Record payment'))
                ->icon('heroicon-o-banknotes')
                ->form([
                    TextInput::make('amount')
                        ->label(__('Amount'))
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->step(0.01),
                    DateTimePicker::make('received_at')
                        ->label(__('Received at'))
                        ->default(now())
                        ->required()
                        ->seconds(false),
                ])
                ->action(function (array $data, Loan $record): void {
                    $record->payments()->create($data);

                    Notification::make()
                        ->title(__('Payment recorded'))
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
