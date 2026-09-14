<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                        UserRole::CollectionCashier->value => UserRole::CollectionCashier->getLabel(),
                        UserRole::Cashier->value => UserRole::Cashier->getLabel(),
                        UserRole::CanteenCashier->value => UserRole::CanteenCashier->getLabel(),
                        UserRole::Inventory->value => UserRole::Inventory->getLabel(),
                    ])
                    ->required()
                    ->default(UserRole::Admin->value)
                    ->native(false),
                DateTimePicker::make('email_verified_at')
                    ->label(__('Email verified at'))
                    ->seconds(false),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(__('Leave blank when editing to keep the current password.')),
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
                            UserRole::CollectionCashier => 'primary',
                            UserRole::Cashier => 'info',
                            UserRole::CanteenCashier => 'warning',
                            UserRole::Inventory => 'success',
                            default => 'gray',
                        };
                    })
                    ->sortable(),
                TextColumn::make('email_verified_at')
                    ->label(__('Verified'))
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('Role'))
                    ->options([
                        UserRole::Admin->value => UserRole::Admin->getLabel(),
                        UserRole::CollectionCashier->value => UserRole::CollectionCashier->getLabel(),
                        UserRole::Cashier->value => UserRole::Cashier->getLabel(),
                        UserRole::CanteenCashier->value => UserRole::CanteenCashier->getLabel(),
                        UserRole::Inventory->value => UserRole::Inventory->getLabel(),
                    ])
                    ->native(false),
            ])
            ->defaultSort('name')
            ->deferLoading()
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
