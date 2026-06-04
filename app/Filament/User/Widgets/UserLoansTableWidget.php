<?php

namespace App\Filament\User\Widgets;

use App\Models\Loan;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UserLoansTableWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('My loans'))
            ->description(__('Loans linked to your account.'))
            ->query(
                Loan::query()
                    ->where('user_id', auth()->id())
                    ->orderByDesc('loan_date')
            )
            ->columns([
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
            ->emptyStateHeading(__('No loans yet'))
            ->emptyStateDescription(__('When you have active or past loans, they will appear here.'));
    }
}
