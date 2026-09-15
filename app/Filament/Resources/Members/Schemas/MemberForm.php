<?php

namespace App\Filament\Resources\Members\Schemas;

use App\Models\Member;
use App\Models\User;
use App\Support\PasswordRules;
use Carbon\Carbon;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

final class MemberForm
{
    /**
     * @return array<int, Component>
     */
    public static function components(): array
    {
        return [
            TextInput::make('name')
                ->label(__('Full name'))
                ->required()
                ->maxLength(255),
            DatePicker::make('date_of_birth')
                ->label(__('Date of birth'))
                ->native(false)
                ->maxDate(now())
                ->live()
                ->afterStateUpdated(function (?string $state, callable $set): void {
                    if (! filled($state)) {
                        $set('age', null);

                        return;
                    }

                    $set('age', Carbon::parse($state)->age);
                }),
            TextInput::make('age')
                ->label(__('Age'))
                ->disabled()
                ->dehydrated(false)
                ->numeric(),
            Radio::make('sex')
                ->label(__('Sex'))
                ->options([
                    'male' => __('Male'),
                    'female' => __('Female'),
                ])
                ->inline()
                ->required(),
            Radio::make('civil_status')
                ->label(__('Civil status'))
                ->options([
                    'single' => __('Single'),
                    'married' => __('Married'),
                    'widowed' => __('Widowed'),
                    'separated' => __('Separated'),
                ])
                ->inline()
                ->required(),
            TextInput::make('address')
                ->label(__('Address'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('contact_number')
                ->label(__('Contact number'))
                ->tel()
                ->required()
                ->maxLength(32),
            TextInput::make('email')
                ->label(__('Email'))
                ->email()
                ->required()
                ->maxLength(255)
                ->helperText(__('A confirmation link is sent to this address. The member can sign in only after they confirm it.'))
                ->rule(function (?Member $record): \Closure {
                    return function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                        $query = Member::query()->where('email', $value);
                        if ($record) {
                            $query->whereKeyNot($record->id);
                        }
                        if ($query->exists()) {
                            $fail(__('The email has already been taken.'));
                        }

                        if (User::query()->where('email', $value)->exists()) {
                            $fail(__('The email has already been taken.'));
                        }
                    };
                }),
            TextInput::make('occupation')
                ->label(__('Occupation / Position'))
                ->required()
                ->maxLength(255),
            TextInput::make('employer_department')
                ->label(__('Employer / Department'))
                ->required()
                ->maxLength(255),
            Toggle::make('is_retiree')
                ->label(__('Retiree'))
                ->helperText(__('Retirees can access the Emergency loan ledger.'))
                ->default(false),
            Toggle::make('is_active')
                ->label(__('Active'))
                ->helperText(__('Inactive members cannot sign in or apply for loans.'))
                ->default(true),
            TextInput::make('password')
                ->label(__('Password'))
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->rule(PasswordRules::rule())
                ->confirmed()
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText(PasswordRules::helperText().' '.__('Leave blank when editing to keep the current password.'))
                ->columnSpanFull(),
            TextInput::make('password_confirmation')
                ->label(__('Confirm password'))
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(false)
                ->columnSpanFull(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withComputedAge(array $data): array
    {
        if (filled($data['date_of_birth'] ?? null)) {
            $data['age'] = Carbon::parse($data['date_of_birth'])->age;
        }

        return $data;
    }
}
