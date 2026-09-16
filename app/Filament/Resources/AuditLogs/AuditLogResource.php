<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $slug = 'security-audit';

    protected static ?string $modelLabel = 'audit event';

    protected static ?string $pluralModelLabel = 'audit events';

    protected static ?string $navigationLabel = 'Security audit';

    protected static ?int $navigationSort = 100;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Event'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('When'))
                            ->dateTime(),
                        TextEntry::make('action')
                            ->label(__('Action')),
                        TextEntry::make('actor.name')
                            ->label(__('Actor'))
                            ->placeholder(__('System')),
                        TextEntry::make('actor_type')
                            ->label(__('Actor type'))
                            ->placeholder('—'),
                        TextEntry::make('subject_type')
                            ->label(__('Subject type'))
                            ->placeholder('—'),
                        TextEntry::make('subject_id')
                            ->label(__('Subject ID'))
                            ->placeholder('—'),
                        TextEntry::make('ip_address')
                            ->label(__('IP address'))
                            ->placeholder('—'),
                        TextEntry::make('user_agent')
                            ->label(__('User agent'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('properties')
                            ->label(__('Details'))
                            ->formatStateUsing(fn (mixed $state): string => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                : (string) ($state ?? '—'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('When'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('action')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label(__('Actor'))
                    ->placeholder(__('System'))
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label(__('Subject'))
                    ->formatStateUsing(function (mixed $state, AuditLog $record): string {
                        if (! filled($state)) {
                            return '—';
                        }

                        $type = class_basename((string) $state);

                        return $type.' #'.$record->subject_id;
                    }),
                TextColumn::make('ip_address')
                    ->label(__('IP'))
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
