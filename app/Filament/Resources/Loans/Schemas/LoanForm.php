<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Enums\LoanStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class LoanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->label(__('Status'))
                    ->options([
                        LoanStatus::Pending->value => LoanStatus::Pending->getLabel(),
                        LoanStatus::Approved->value => LoanStatus::Approved->getLabel(),
                        LoanStatus::Rejected->value => LoanStatus::Rejected->getLabel(),
                    ])
                    ->required()
                    ->native(false),
                DateTimePicker::make('approved_at')
                    ->label(__('Approved at'))
                    ->seconds(false)
                    ->visible(fn (callable $get): bool => $get('status') === LoanStatus::Approved->value),
            ]);
    }
}
