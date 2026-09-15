<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Support\PasswordRules;
use App\Support\StaffAccount;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'user';

    protected static ?string $pluralModelLabel = 'users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 11;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Full name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('Email address'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('role')
                    ->label(__('Role'))
                    ->options([
                        UserRole::Admin->value => UserRole::Admin->getLabel(),
                        UserRole::Cashier->value => UserRole::Cashier->getLabel(),
                        UserRole::CanteenCashier->value => UserRole::CanteenCashier->getLabel(),
                        UserRole::Inventory->value => UserRole::Inventory->getLabel(),
                    ])
                    ->required()
                    ->default(UserRole::Admin->value)
                    ->native(false),
                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->rule(PasswordRules::rule())
                    ->confirmed()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(PasswordRules::helperText().' '.__('Leave blank when editing to keep the current password.')),
                TextInput::make('password_confirmation')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(function (mixed $state): string {
                        if ($state instanceof UserRole) {
                            return $state->getLabel();
                        }

                        if (is_string($state)) {
                            return UserRole::tryFrom($state)?->getLabel() ?? $state;
                        }

                        return '—';
                    })
                    ->color(function (mixed $state): string {
                        $role = $state instanceof UserRole
                            ? $state
                            : (is_string($state) ? UserRole::tryFrom($state) : null);

                        return match ($role) {
                            UserRole::Admin => 'danger',
                            UserRole::Cashier => 'info',
                            UserRole::CanteenCashier => 'warning',
                            UserRole::Inventory => 'success',
                            default => 'gray',
                        };
                    })
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('Role'))
                    ->options([
                        UserRole::Admin->value => UserRole::Admin->getLabel(),
                        UserRole::Cashier->value => UserRole::Cashier->getLabel(),
                        UserRole::CanteenCashier->value => UserRole::CanteenCashier->getLabel(),
                        UserRole::Inventory->value => UserRole::Inventory->getLabel(),
                    ])
                    ->native(false),
            ])
            ->defaultSort('name')
            ->deferLoading()
            ->recordActions([
                EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        /** @var User $record */
                        return StaffAccount::update($record, $data);
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', '!=', UserRole::User->value);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
