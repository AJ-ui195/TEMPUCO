<?php

namespace App\Filament\Resources\Loans\Tables;

use App\Enums\LoanStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('Member'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label(__('Email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
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
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => (string) $state),
                TextColumn::make('loan_category')
                    ->label(__('Category'))
                    ->formatStateUsing(fn (mixed $state): string => $state?->getLabel() ?? '—')
                    ->toggleable(),
                TextColumn::make('loan_amount')
                    ->label(__('Loan amount'))
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('loan_period_months')
                    ->label(__('Loan period'))
                    ->formatStateUsing(fn (mixed $state): string => ((int) $state).' '.__('months'))
                    ->sortable(),
                TextColumn::make('installment_amount')
                    ->label(__('Installment amount'))
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('loan_date')
                    ->label(__('Date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Submitted'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
