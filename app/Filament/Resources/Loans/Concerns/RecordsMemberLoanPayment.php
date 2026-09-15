<?php

namespace App\Filament\Resources\Loans\Concerns;

use App\Enums\LoanStatus;
use App\Models\Contracts\MemberLoan;
use App\Support\CharacterLoanLedgerEntries;
use App\Support\RecordMemberLoanPayment;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use InvalidArgumentException;

trait RecordsMemberLoanPayment
{
    /**
     * @return array<int, Action>
     */
    protected function recordPaymentAction(): array
    {
        return [
            Action::make('recordPayment')
                ->label(__('Record payment'))
                ->icon('heroicon-o-banknotes')
                ->visible(fn (): bool => $this->getRecord()->status === LoanStatus::Approved)
                ->fillForm(fn (): array => RecordMemberLoanPayment::defaults($this->getRecord()))
                ->form([
                    Select::make('kind')
                        ->label(__('Payment type'))
                        ->options([
                            CharacterLoanLedgerEntries::KIND_INTEREST => __('Interest'),
                            CharacterLoanLedgerEntries::KIND_PRINCIPAL => __('Released amount'),
                        ])
                        ->required()
                        ->native(false)
                        ->live()
                        ->visible(fn (): bool => RecordMemberLoanPayment::isCharacterFamily($this->getRecord()))
                        ->afterStateUpdated(function (?string $state, callable $set): void {
                            /** @var MemberLoan $record */
                            $record = $this->getRecord();
                            if ($state === CharacterLoanLedgerEntries::KIND_INTEREST) {
                                $set('amount', CharacterLoanLedgerEntries::periodInterest($record));

                                return;
                            }

                            $remaining = RecordMemberLoanPayment::remainingPrincipal($record);
                            $set('amount', $remaining > RecordMemberLoanPayment::EPSILON ? $remaining : null);
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
                        ->required(fn (): bool => RecordMemberLoanPayment::requiresOfficialReceipt($this->getRecord())),
                    DateTimePicker::make('received_at')
                        ->label(__('Received at'))
                        ->default(now())
                        ->required()
                        ->seconds(false),
                ])
                ->action(function (array $data): void {
                    try {
                        RecordMemberLoanPayment::apply(
                            $this->getRecord(),
                            (float) $data['amount'],
                            (string) ($data['kind'] ?? CharacterLoanLedgerEntries::KIND_PRINCIPAL),
                            $data['official_receipt_no'] ?? null,
                            $data['received_at'] ?? now(),
                        );
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('Payment recorded'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
