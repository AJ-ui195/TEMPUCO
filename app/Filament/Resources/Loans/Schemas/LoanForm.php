<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Support\PesoInput;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'lg' => 2,
            ])
            ->components([
                Group::make([
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
                            PesoInput::decorate(
                                TextInput::make('approved_amount')
                                    ->label(__('Amount approved'))
                                    ->minValue(0)
                            ),
                            DatePicker::make('minutes_date')
                                ->label(__('Recorded in minutes'))
                                ->native(false),
                        ])
                        ->columns(2)
                        ->collapsed(),

                    Actions::make([
                        Action::make('save')
                            ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                        Action::make('cancel')
                            ->label(__('filament-panels::resources/pages/edit-record.form.actions.cancel.label'))
                            ->color('gray')
                            ->alpineClickHandler('window.history.back()'),
                    ]),
                ])
                    ->columns(1),

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
            ]);
    }
}
