<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Support\MemberQrCode;
use BackedEnum;
use Filament\Actions\Action;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'members';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'member';

    protected static ?string $pluralModelLabel = 'members';

    protected static ?string $navigationLabel = 'Members';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('role')
                    ->label('Role')
                    ->options([
                        UserRole::Admin->value => UserRole::Admin->getLabel(),
                        UserRole::User->value => UserRole::User->getLabel(),
                    ])
                    ->required()
                    ->default(UserRole::User->value)
                    ->native(false),
                TextInput::make('address')
                    ->maxLength(255),
                TextInput::make('cellphone')
                    ->label('Cellphone #')
                    ->tel()
                    ->maxLength(32),
                DateTimePicker::make('email_verified_at')
                    ->label('Email verified at')
                    ->seconds(false),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Leave blank when editing to keep the current password.'),
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
                    ->label('Email')
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
                            UserRole::User => 'gray',
                            default => 'gray',
                        };
                    })
                    ->sortable(),
                TextColumn::make('address')
                    ->label('Address')
                    ->searchable()
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('cellphone')
                    ->label('Cellphone #')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                ImageColumn::make('qr_code')
                    ->label('QR code')
                    ->getStateUsing(fn (User $record): string => MemberQrCode::dataUriFor($record))
                    ->imageHeight(64)
                    ->imageWidth(64)
                    ->extraImgAttributes(fn (User $record): array => [
                        'alt' => "QR code for {$record->name}",
                    ]),
            ])
            ->defaultSort('name')
            ->deferLoading()
            ->recordActions([
                Action::make('printQrCode')
                    ->label(__('Print QR code'))
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (User $record): string => route('admin.members.print-qr', [
                        'user' => $record,
                        'auto' => 1,
                    ]))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
