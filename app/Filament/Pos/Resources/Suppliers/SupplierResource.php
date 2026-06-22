<?php

namespace App\Filament\Pos\Resources\Suppliers;

use App\Filament\Pos\Resources\Suppliers\Pages\ManageSuppliers;
use App\Models\PosSupplier;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = PosSupplier::class;

    protected static ?string $slug = 'suppliers';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'supplier';

    protected static ?string $pluralModelLabel = 'suppliers';

    protected static ?string $navigationLabel = 'Suppliers';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Supplier name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('contact_person')
                    ->label(__('Contact person'))
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label(__('Phone'))
                    ->tel()
                    ->maxLength(32),
                TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->maxLength(255),
                Textarea::make('address')
                    ->label(__('Address'))
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label(__('Notes'))
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Supplier'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_person')
                    ->label(__('Contact'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->placeholder('—'),
                TextColumn::make('inventory_items_count')
                    ->label(__('Products'))
                    ->counts('inventoryItems')
                    ->alignEnd(),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
            ])
            ->defaultSort('name')
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

    public static function getPages(): array
    {
        return [
            'index' => ManageSuppliers::route('/'),
        ];
    }
}
