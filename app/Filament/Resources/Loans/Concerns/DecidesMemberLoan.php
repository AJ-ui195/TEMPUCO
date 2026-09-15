<?php

namespace App\Filament\Resources\Loans\Concerns;

use App\Enums\LoanStatus;
use App\Support\LoanDecisionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

trait DecidesMemberLoan
{
    /**
     * @return array<int, Action>
     */
    protected function decisionActions(): array
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
        ];
    }
}
