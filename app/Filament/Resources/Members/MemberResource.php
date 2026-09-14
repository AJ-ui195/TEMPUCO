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
                TextColumn::make('contact_number')
                    ->label(__('Contact number'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('is_retiree')
                    ->label(__('Retiree'))
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
            ->defaultSort('name')
            ->deferLoading()
            ->recordActions([
                Action::make('printQrCode')
                    ->label(__('Print QR code'))
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (Member $record): string => PrintMemberQrCode::printUrl($record))
                    ->openUrlInNewTab(),
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
