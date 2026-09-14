<?php

namespace App\Filament\Resources\Members\Schemas;

use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

final class MemberForm
{
    /**
     * @return array<int, \Filament\Forms\Components\Component>
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
                ->helperText(__('Marks this member as a retiree.'))
                ->default(false),
            TextInput::make('password')
                ->label(__('Password'))
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->minLength(8)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText(__('Used by the member to sign in to the Members Portal. Leave blank when editing to keep the current password.'))
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
