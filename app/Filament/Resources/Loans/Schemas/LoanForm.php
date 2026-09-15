<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Enums\LoanStatus;
use App\Models\Loan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
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
                        Placeholder::make('status_display')
                            ->label(__('Status'))
                            ->content(fn (?Loan $record): string => $record?->status?->getLabel()
                                ?? LoanStatus::Pending->getLabel()),
                        Placeholder::make('approved_at_display')
                            ->label(__('Approved at'))
                            ->content(fn (?Loan $record): string => $record?->approved_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?: '—'),
                        Placeholder::make('rejected_at_display')
                            ->label(__('Rejected at'))
                            ->content(fn (?Loan $record): string => $record?->rejected_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?: '—'),
                        Placeholder::make('decided_by_display')
                            ->label(__('Decided by'))
                            ->content(fn (?Loan $record): string => $record?->decidedBy?->name ?: '—'),
                    ])
                    ->columns(2),

                Section::make(__('Cooperative certification'))
                    ->relationship('certification')
                    ->schema([
                        TextInput::make('borrower_name')
                            ->label(__('Name of borrower'))
                            ->maxLength(255),
                        TextInput::make('fixed_savings_deposits')
                            ->label(__('Fixed savings deposits'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        TextInput::make('standing_loan')
                            ->label(__('Standing loan'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        DatePicker::make('date_of_birth')
                            ->label(__('Date of birth'))
                            ->native(false),
                        Textarea::make('home_address')
                            ->label(__('Home address'))
                            ->rows(2)
                            ->columnSpanFull(),
                        DatePicker::make('treasurer_signed_at')
                            ->label(__('Treasurer date'))
                            ->native(false),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make(__('Committee approval'))
                    ->relationship('committeeDecision')
                    ->schema([
                        DatePicker::make('meeting_date')
                            ->label(__('Meeting date'))
                            ->native(false),
                        Textarea::make('conditions_notes')
                            ->label(__('Conditions / changes'))
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('approved_amount')
                            ->label(__('Amount approved'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        DatePicker::make('minutes_date')
                            ->label(__('Recorded in minutes'))
                            ->native(false),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
