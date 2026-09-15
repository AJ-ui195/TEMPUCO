<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Enums\LoanStatus;
use App\Filament\Resources\Loans\LoanResource;
use App\Models\Loan;
use App\Support\CharacterLoanLedgerEntries;
use App\Support\LoanDecisionService;
use App\Support\LoanTypes;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLoan extends ViewRecord
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('Approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->getRecord()->status === LoanStatus::Pending)
                ->authorize(fn (): bool => auth()->user()?->can('approve', $this->getRecord()) ?? false)
                ->requiresConfirmation()
                ->modalHeading(__('Approve this loan?'))
                ->schema([
                    TextInput::make('current_password')
                        ->label(__('Current password'))
                        ->password()
                        ->revealable()
                        ->required()
                        ->currentPassword(),
                    Textarea::make('decision_notes')
                        ->label(__('Notes'))
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(LoanDecisionService::class)->approve(
                        auth()->user(),
                        $this->getRecord(),
                        $data['current_password'],
                        $data['decision_notes'] ?? null,
                    );

                    Notification::make()
                        ->title(__('Loan approved'))
                        ->success()
                        ->send();
                }),
            Action::make('reject')
                ->label(__('Reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->getRecord()->status === LoanStatus::Pending)
                ->authorize(fn (): bool => auth()->user()?->can('reject', $this->getRecord()) ?? false)
                ->requiresConfirmation()
                ->modalHeading(__('Reject this loan?'))
                ->schema([
                    TextInput::make('current_password')
                        ->label(__('Current password'))
                        ->password()
                        ->revealable()
                        ->required()
                        ->currentPassword(),
                    Textarea::make('decision_notes')
                        ->label(__('Reason'))
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(LoanDecisionService::class)->reject(
                        auth()->user(),
                        $this->getRecord(),
                        $data['current_password'],
                        (string) $data['decision_notes'],
                    );

                    Notification::make()
                        ->title(__('Loan rejected'))
                        ->success()
                        ->send();
                }),
            Action::make('recordPayment')
                ->label(__('Record payment'))
                ->icon('heroicon-o-banknotes')
                ->visible(fn (): bool => $this->getRecord()->status === LoanStatus::Approved)
                ->authorize(fn (): bool => auth()->user()?->can('recordPayment', $this->getRecord()) ?? false)
                ->fillForm(function (): array {
                    /** @var Loan $record */
                    $record = $this->getRecord();
                    $defaults = [
                        'received_at' => now(),
                        'kind' => CharacterLoanLedgerEntries::KIND_PRINCIPAL,
                    ];

                    $type = strtoupper(trim((string) $record->loan_type));

                    if ($type === LoanTypes::QUICK) {
                        $paid = (float) $record->payments()
                            ->where(function ($query): void {
                                $query->whereNull('kind')
                                    ->orWhere('kind', CharacterLoanLedgerEntries::KIND_PRINCIPAL);
                            })
                            ->sum('amount');
                        $remaining = round(max(0, (float) $record->loan_amount - $paid), 2);
                        if ($remaining > 0) {
                            $defaults['amount'] = $remaining;
                        }
                    }

                    if (in_array($type, [LoanTypes::CHARACTER, LoanTypes::EMERGENCY], true)) {
                        $defaults['kind'] = CharacterLoanLedgerEntries::KIND_INTEREST;
                        $defaults['amount'] = CharacterLoanLedgerEntries::periodInterest($record);
                    }

                    return $defaults;
                })
                ->form([
                    Select::make('kind')
                        ->label(__('Payment type'))
                        ->options([
                            CharacterLoanLedgerEntries::KIND_INTEREST => __('Interest'),
                            CharacterLoanLedgerEntries::KIND_PRINCIPAL => __('Principal'),
                        ])
                        ->required()
                        ->native(false)
                        ->live()
                        ->visible(fn (): bool => in_array(
                            strtoupper(trim((string) $this->getRecord()->loan_type)),
                            [LoanTypes::CHARACTER, LoanTypes::EMERGENCY],
                            true,
                        ))
                        ->afterStateUpdated(function (?string $state, callable $set): void {
                            /** @var Loan $record */
                            $record = $this->getRecord();
                            if ($state === CharacterLoanLedgerEntries::KIND_INTEREST) {
                                $set('amount', CharacterLoanLedgerEntries::periodInterest($record));

                                return;
                            }

                            $principalPaid = (float) $record->payments()
                                ->where(function ($query): void {
                                    $query->whereNull('kind')
                                        ->orWhere('kind', CharacterLoanLedgerEntries::KIND_PRINCIPAL);
                                })
                                ->sum('amount');
                            $remaining = round(max(0, (float) $record->loan_amount - $principalPaid), 2);
                            $set('amount', $remaining > 0 ? $remaining : null);
                        }),
                    TextInput::make('amount')
                        ->label(__('Amount'))
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->step(0.01),
                    TextInput::make('official_receipt_no')
                        ->label(__('O.R. #'))
                        ->maxLength(64)
                        ->required(fn (): bool => in_array(
                            strtoupper(trim((string) $this->getRecord()->loan_type)),
                            [LoanTypes::QUICK, LoanTypes::CHARACTER, LoanTypes::EMERGENCY],
                            true,
                        )),
                    DateTimePicker::make('received_at')
                        ->label(__('Received at'))
                        ->default(now())
                        ->required()
                        ->seconds(false),
                ])
                ->action(function (array $data): void {
                    /** @var Loan $record */
                    $record = $this->getRecord();
                    $type = strtoupper(trim((string) $record->loan_type));
                    $kind = $data['kind']
                        ?? (in_array($type, [LoanTypes::CHARACTER, LoanTypes::EMERGENCY], true)
                            ? CharacterLoanLedgerEntries::KIND_INTEREST
                            : CharacterLoanLedgerEntries::KIND_PRINCIPAL);

                    app(LoanDecisionService::class)->recordPayment(auth()->user(), $record, [
                        'amount' => $data['amount'],
                        'kind' => $kind,
                        'official_receipt_no' => $data['official_receipt_no'] ?? null,
                        'received_at' => $data['received_at'],
                    ]);

                    Notification::make()
                        ->title(__('Payment recorded'))
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
