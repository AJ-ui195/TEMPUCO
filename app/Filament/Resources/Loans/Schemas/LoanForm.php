<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Enums\LoanStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Status'))
                    ->schema([
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
                    ])
                    ->columns(2),

                Section::make(__('Cooperative certification'))
                    ->schema([
                        TextInput::make('cert_borrower_name')
                            ->label(__('Name of borrower'))
                            ->maxLength(255),
                        TextInput::make('cert_fixed_savings_deposits')
                            ->label(__('Fixed savings deposits'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        TextInput::make('cert_standing_loan')
                            ->label(__('Standing loan'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        DatePicker::make('cert_date_of_birth')
                            ->label(__('Date of birth'))
                            ->native(false),
                        Textarea::make('cert_home_address')
                            ->label(__('Home address'))
                            ->rows(2)
                            ->columnSpanFull(),
                        DatePicker::make('cert_treasurer_signed_at')
                            ->label(__('Treasurer date'))
                            ->native(false),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make(__('Committee approval'))
                    ->schema([
                        DatePicker::make('committee_meeting_date')
                            ->label(__('Meeting date'))
                            ->native(false),
                        Textarea::make('committee_conditions_notes')
                            ->label(__('Conditions / changes'))
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('committee_approved_amount')
                            ->label(__('Amount approved'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        DatePicker::make('committee_minutes_date')
                            ->label(__('Recorded in minutes'))
                            ->native(false),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
