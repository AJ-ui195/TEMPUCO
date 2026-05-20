<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Enums\LoanStatus;
use App\Models\Loan;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LoanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label(__('Member')),
                TextEntry::make('user.email')
                    ->label(__('Email'))
                    ->copyable(),
                TextEntry::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof LoanStatus ? $state->getLabel() : (string) $state),
                TextEntry::make('apply_loan')
                    ->label(__('Apply loan')),
                TextEntry::make('loan_amount')
                    ->label(__('Loan amount'))
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),
                TextEntry::make('loan_period_months')
                    ->label(__('Loan period'))
                    ->formatStateUsing(fn (mixed $state): string => ((int) $state).' '.__('months')),
                TextEntry::make('installment_amount')
                    ->label(__('Installment amount'))
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),
                TextEntry::make('loan_date')
                    ->label(__('Date'))
                    ->date(),
                TextEntry::make('approved_at')
                    ->label(__('Approved at'))
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->label(__('Submitted'))
                    ->dateTime(),
                TextEntry::make('payments_total')
                    ->label(__('Total received'))
                    ->state(fn (Loan $record): string => number_format((float) $record->payments()->sum('amount'), 2)),
            ]);
    }
}
