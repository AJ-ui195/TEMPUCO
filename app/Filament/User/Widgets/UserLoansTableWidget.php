<?php

namespace App\Filament\User\Widgets;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Support\PrintMemberLoan;
use Filament\Actions\Action;
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
            ->query(fn (): Builder => Loan::query()->forUser(auth()->user())->orderedByLoanDate())
            ->columns([
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof LoanStatus ? $state->getLabel() : (string) $state)
                    ->color(fn (mixed $state): string => match ($state instanceof LoanStatus ? $state : LoanStatus::tryFrom((string) $state)) {
                        LoanStatus::Approved => 'success',
                        LoanStatus::Rejected => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('loan_type')
                    ->label(__('Loan type'))
                    ->searchable()
                    ->formatStateUsing(fn (mixed $state): string => (string) $state),
                TextColumn::make('loan_category')
                    ->label(__('Category'))
                    ->formatStateUsing(fn (mixed $state): string => $state?->getLabel() ?? '—'),
                TextColumn::make('loan_amount')
                    ->label(__('Loan amount'))
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),
                TextColumn::make('loan_period_months')
                    ->label(__('Loan period'))
                    ->formatStateUsing(fn (mixed $state): string => ((int) $state).' '.__('months')),
                TextColumn::make('installment_amount')
                    ->label(__('Installment amount'))
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),
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
            ])
            ->emptyStateHeading(__('No loans yet'))
            ->emptyStateDescription(__('When you have active or past loans, they will appear here.'));
    }
}
