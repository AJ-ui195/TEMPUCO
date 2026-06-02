<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Enums\LoanCategory;
use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use App\Models\Loan;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Application'))
                    ->schema([
                        TextEntry::make('user.name')
                            ->label(__('Member')),
                        TextEntry::make('user.email')
                            ->label(__('Email'))
                            ->copyable(),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->formatStateUsing(fn (mixed $state): string => $state instanceof LoanStatus ? $state->getLabel() : (string) $state),
                        TextEntry::make('loan_category')
                            ->label(__('Loan category'))
                            ->formatStateUsing(fn (mixed $state): string => $state instanceof LoanCategory ? $state->getLabel() : '—')
                            ->placeholder('—'),
                        TextEntry::make('applicant_name')
                            ->label(__('Applicant name')),
                        TextEntry::make('applicant_address')
                            ->label(__('Address'))
                            ->columnSpanFull(),
                        TextEntry::make('loan_type')
                            ->label(__('Type of loan')),
                        TextEntry::make('loan_amount')
                            ->label(__('Loan amount (PHP)'))
                            ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),
                        TextEntry::make('loan_amount_words')
                            ->label(__('Amount in words'))
                            ->placeholder('—'),
                        TextEntry::make('loan_period_months')
                            ->label(__('Loan period'))
                            ->formatStateUsing(fn (mixed $state): string => ((int) $state).' '.__('months')),
                        TextEntry::make('installment_amount')
                            ->label(__('Monthly installment'))
                            ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),
                        TextEntry::make('first_payment_due_date')
                            ->label(__('First payment due'))
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('purpose_of_loan')
                            ->label(__('Purpose of loan'))
                            ->formatStateUsing(fn (mixed $state): string => $state instanceof LoanPurpose ? $state->getLabel() : '—')
                            ->placeholder('—'),
                        TextEntry::make('purpose_of_loan_other')
                            ->label(__('Purpose (others)'))
                            ->visible(fn (Loan $record): bool => $record->purpose_of_loan === LoanPurpose::Others)
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('mode_of_payment')
                            ->label(__('Mode of payment'))
                            ->formatStateUsing(fn (mixed $state): string => $state instanceof ModeOfPayment ? $state->getLabel() : '—')
                            ->placeholder('—'),
                        TextEntry::make('loan_purpose')
                            ->label(__('Purpose notes'))
                            ->columnSpanFull()
                            ->placeholder('—'),
                        TextEntry::make('applicant_signed_at')
                            ->label(__('Applicant signed on'))
                            ->date(),
                        TextEntry::make('applicant_signature_name')
                            ->label(__('Applicant signature')),
                        TextEntry::make('loan_date')
                            ->label(__('Submitted'))
                            ->date(),
                        TextEntry::make('approved_at')
                            ->label(__('Approved at'))
                            ->dateTime()
                            ->placeholder('—'),
                    ])
                    ->columns(2),

                Section::make(__('Cooperative certification'))
                    ->schema([
                        TextEntry::make('cert_borrower_name')
                            ->label(__('Name of borrower'))
                            ->placeholder('—'),
                        TextEntry::make('cert_fixed_savings_deposits')
                            ->label(__('Fixed savings deposits'))
                            ->formatStateUsing(fn (mixed $state): string => filled($state) ? number_format((float) $state, 2) : '—'),
                        TextEntry::make('cert_standing_loan')
                            ->label(__('Standing loan'))
                            ->formatStateUsing(fn (mixed $state): string => filled($state) ? number_format((float) $state, 2) : '—'),
                        TextEntry::make('cert_date_of_birth')
                            ->label(__('Date of birth'))
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('cert_home_address')
                            ->label(__('Home address'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('cert_treasurer_signed_at')
                            ->label(__('Treasurer date'))
                            ->date()
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make(__('Committee approval'))
                    ->schema([
                        TextEntry::make('committee_meeting_date')
                            ->label(__('Meeting date'))
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('committee_conditions_notes')
                            ->label(__('Conditions / changes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('committee_approved_amount')
                            ->label(__('Amount approved'))
                            ->formatStateUsing(fn (mixed $state): string => filled($state) ? number_format((float) $state, 2) : '—'),
                        TextEntry::make('committee_minutes_date')
                            ->label(__('Recorded in minutes'))
                            ->date()
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make(__('Payments'))
                    ->schema([
                        TextEntry::make('payments_total')
                            ->label(__('Total received'))
                            ->state(fn (Loan $record): string => number_format((float) $record->payments()->sum('amount'), 2)),
                    ]),
            ]);
    }
}
