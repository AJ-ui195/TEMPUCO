<?php

namespace App\Filament\Pos\Resources\Members;

use App\Enums\UserRole;
use App\Filament\Pos\Resources\Members\Pages\ManageMembers;
use App\Models\User;
use App\Support\MemberQrCode;
use App\Support\PrintMemberQrCode;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class MemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'members';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'member';

    protected static ?string $pluralModelLabel = 'members';

    protected static ?string $navigationLabel = 'Members';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('role')
                    ->default(UserRole::User->value),
                TextInput::make('name')
                    ->label(__('Full name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->label(__('Email address'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('cellphone')
                    ->label(__('Cellphone #'))
                    ->tel()
                    ->maxLength(32),
                TextInput::make('address')
                    ->label(__('Address'))
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('password')
                    ->label(__('Password'))
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(__('Used by the member to sign in to the Members Portal. Leave blank when editing to keep the current password.'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Member'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('cellphone')
                    ->label(__('Cellphone #'))
                    ->searchable()
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->deferLoading()
            ->recordActions([
                Action::make('viewQrCode')
                    ->label(__('View QR'))
                    ->icon(Heroicon::OutlinedQrCode)
                    ->modalHeading(fn (User $record): string => __('Member QR code — :name', [
                        'name' => $record->name,
                    ]))
                    ->modalContent(fn (User $record): View => view(
                        'filament.pos.member-qr-modal',
                        [
                            'user' => $record,
                            'qrCodeDataUri' => MemberQrCode::dataUriFor($record, scale: 4),
                        ],
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close'))
                    ->extraModalFooterActions(fn (User $record): array => [
                        Action::make('printQrFromView')
                            ->label(__('Print QR code'))
                            ->icon(Heroicon::OutlinedPrinter)
                            ->url(PrintMemberQrCode::printUrl($record))
                            ->openUrlInNewTab()
                            ->color('primary'),
                    ]),
                Action::make('printQrCode')
                    ->label(__('Print QR code'))
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (User $record): string => PrintMemberQrCode::printUrl($record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->members();
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMembers::route('/'),
        ];
    }
}
