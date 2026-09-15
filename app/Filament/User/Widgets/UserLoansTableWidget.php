<?php

namespace App\Filament\User\Widgets;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\Member;
use App\Support\LoanApplicationService;
use App\Support\PrintMemberLoan;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UserLoansTableWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('My loans'))
            ->description(__('Track the status and details of your loan applications.'))
            ->striped()
            ->paginated([5, 10, 25])
            ->query(fn (): Builder => $this->memberLoansQuery())
            ->columns([
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof LoanStatus ? $state->getLabel() : (string) $state)
                    ->color(fn (mixed $state): string => match ($state instanceof LoanStatus ? $state : LoanStatus::tryFrom((string) $state)) {
                        LoanStatus::Approved => 'success',
                        LoanStatus::Rejected => 'danger',
                        LoanStatus::AwaitingVerification => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('loan_type')
                    ->label(__('Loan type'))
                    ->searchable()
                    ->formatStateUsing(fn (mixed $state): string => (string) $state),
                TextColumn::make('loan_category')
                    ->label(__('Category'))
                    ->formatStateUsing(fn (mixed $state): string => $state?->getLabel() ?? '—')
                    ->hiddenFrom('md'),
                TextColumn::make('loan_amount')
                    ->label(__('Loan amount'))
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),
                TextColumn::make('loan_period_months')
                    ->label(__('Loan period'))
                    ->formatStateUsing(fn (mixed $state): string => ((int) $state).' '.__('months'))
                    ->hiddenFrom('md'),
                TextColumn::make('installment_amount')
                    ->label(__('Installment amount'))
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2))
                    ->hiddenFrom('md'),
                TextColumn::make('loan_date')
                    ->label(__('Date'))
                    ->date(),
            ])
            ->recordActions([
                Action::make('printLoanDetails')
                    ->label(__('Print details'))
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (Loan $record): string => PrintMemberLoan::printUrl($record))
                    ->openUrlInNewTab()
                    ->disabled(fn (Loan $record): bool => $record->status !== LoanStatus::Approved)
                    ->tooltip(fn (Loan $record): ?string => $record->status !== LoanStatus::Approved
                        ? __('Available only for approved loans.')
                        : __('Open printable loan details form')),
                Action::make('resendConfirmation')
                    ->label(__('Resend confirmation email'))
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->visible(fn (Loan $record): bool => $record->isAwaitingEmailConfirmation())
                    ->action(function (Loan $record): void {
                        /** @var Member $member */
                        $member = Filament::auth()->user();
                        app(LoanApplicationService::class)->resendConfirmation($member, $record);

                        Notification::make()
                            ->title(__('Confirmation email sent'))
                            ->body(__('Check :email and confirm the application before TEMPUCO can review it.', [
                                'email' => $member->email,
                            ]))
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading(__('No loans yet'))
            ->emptyStateDescription(__('When you have active or past loans, they will appear here.'));
    }

    protected function memberLoansQuery(): Builder
    {
        /** @var Member $user */
        $user = Filament::auth()->user();

        return Loan::query()->forUser($user)->orderedByLoanDate();
    }
}
