<?php

namespace App\Filament\Resources\Members;

use App\Filament\Resources\Members\Pages\ManageMembers;
use App\Filament\Resources\Members\Schemas\MemberForm;
use App\Models\Member;
use App\Support\MemberAccount;
use App\Support\MemberQrCode;
use App\Support\PrintMemberQrCode;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

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
            ->components(MemberForm::components())
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Full name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('createdBy.name')
                    ->label(__('Created by'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('email_status')
                    ->label(__('Email status'))
                    ->badge()
                    ->getStateUsing(fn (Member $record): string => $record->hasVerifiedEmail()
                        ? __('Confirmed')
                        : __('Awaiting confirmation'))
                    ->color(fn (Member $record): string => $record->hasVerifiedEmail() ? 'success' : 'warning'),
                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('contact_number')
                    ->label(__('Contact number'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('is_retiree')
                    ->label(__('Retiree'))
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
                ImageColumn::make('qr_code')
                    ->label(__('QR code'))
                    ->getStateUsing(fn (Member $record): string => MemberQrCode::dataUriFor($record))
                    ->imageHeight(64)
                    ->imageWidth(64)
                    ->extraImgAttributes(fn (Member $record): array => [
                        'alt' => "QR code for {$record->name}",
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->recordActions([
                Action::make('resendConfirmation')
                    ->label(__('Resend confirmation'))
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->visible(fn (Member $record): bool => ! $record->hasVerifiedEmail())
                    ->requiresConfirmation()
                    ->modalHeading(__('Resend confirmation email'))
                    ->modalDescription(fn (Member $record): string => __('Send a new confirmation link to :email?', [
                        'email' => $record->email,
                    ]))
                    ->action(function (Member $record): void {
                        MemberAccount::resendConfirmation($record);

                        Notification::make()
                            ->title(__('Confirmation email sent'))
                            ->success()
                            ->send();
                    }),
                Action::make('printQrCode')
                    ->label(__('Print QR code'))
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (Member $record): string => PrintMemberQrCode::printUrl($record))
                    ->openUrlInNewTab()
                    ->disabled(fn (Member $record): bool => ! $record->hasVerifiedEmail())
                    ->tooltip(fn (Member $record): ?string => $record->hasVerifiedEmail()
                        ? null
                        : __('Available after the member confirms their email.')),
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data): array => MemberForm::withComputedAge($data))
                    ->using(function (Model $record, array $data): Model {
                        /** @var Member $record */
                        return MemberAccount::update($record, $data);
                    }),
                DeleteAction::make()
                    ->using(function (Model $record): void {
                        /** @var Member $record */
                        MemberAccount::delete($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records): void {
                            $records->each(fn (Member $record) => MemberAccount::delete($record));
                        }),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('createdBy');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMembers::route('/'),
        ];
    }
}
