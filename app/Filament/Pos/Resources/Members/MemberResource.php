<?php

namespace App\Filament\Pos\Resources\Members;

use App\Filament\Pos\Resources\Members\Pages\ManageMembers;
use App\Filament\Resources\Members\Schemas\MemberForm;
use App\Models\Member;
use App\Support\MemberAccount;
use App\Support\MemberQrCode;
use App\Support\PrintMemberQrCode;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

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
            ->components(MemberForm::components())
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
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->recordActions([
                Action::make('viewQrCode')
                    ->label(__('View QR'))
                    ->icon(Heroicon::OutlinedQrCode)
                    ->modalHeading(fn (Member $record): string => __('Member QR code — :name', [
                        'name' => $record->name,
                    ]))
                    ->modalContent(fn (Member $record): View => view(
                        'filament.pos.member-qr-modal',
                        [
                            'user' => $record,
                            'qrCodeDataUri' => MemberQrCode::dataUriFor($record, scale: 4),
                        ],
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close'))
                    ->extraModalFooterActions(fn (Member $record): array => [
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
                    ->url(fn (Member $record): string => PrintMemberQrCode::printUrl($record))
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data): array => MemberForm::withComputedAge($data))
                    ->using(function (Model $record, array $data): Model {
                        /** @var Member $record */
                        return MemberAccount::update($record, $data);
                    }),
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
