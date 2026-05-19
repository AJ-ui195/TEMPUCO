<?php

namespace App\Filament\Resources\Loans\Schemas;

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
                TextEntry::make('created_at')
                    ->label(__('Submitted'))
                    ->dateTime(),
            ]);
    }
}
