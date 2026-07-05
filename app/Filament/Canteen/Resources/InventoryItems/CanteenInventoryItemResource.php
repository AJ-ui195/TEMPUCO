<?php

namespace App\Filament\Canteen\Resources\InventoryItems;

use App\Filament\Concerns\ConfiguresInventoryProductBarcode;
use App\Filament\Canteen\Resources\InventoryItems\Pages\ManageCanteenInventoryItems;
use App\Models\PosCanteenInventoryItem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CanteenInventoryItemResource extends Resource
{
    use ConfiguresInventoryProductBarcode;

    protected static ?string $model = PosCanteenInventoryItem::class;

    protected static ?string $slug = 'products';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'product';

    protected static ?string $pluralModelLabel = 'products';

    protected static ?string $navigationLabel = 'Products';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Product name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                ...self::inventoryBarcodeFormFields(),
                Textarea::make('description')
                    ->label(__('Description'))
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('supplier_id')
                    ->label(__('Supplier'))
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->columnSpanFull(),
                TextInput::make('quantity')
                    ->label(__('Quantity in stock'))
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('unit_price')
                    ->label(__('Unit price (PHP)'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('₱')
                    ->default(0),
                TextInput::make('cost')
                    ->label(__('Cost (PHP)'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('₱')
                    ->default(0)
                    ->helperText(__('Purchase or unit cost.')),
                TextInput::make('reorder_level')
                    ->label(__('Reorder level'))
                    ->integer()
                    ->minValue(0)
                    ->helperText(__('Alert when stock is at or below this level.')),
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
                    ->label(__('Product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label(__('SKU'))
                    ->formatStateUsing(function (?string $state): string {
                        if (blank($state)) {
                            return '—';
                        }

                        if (ctype_digit($state) && in_array(strlen($state), [8, 12, 13], true)) {
                            return \App\Support\ProductBarcodeRenderer::formatUpcLabel($state);
                        }

                        return $state;
                    })
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('supplier.name')
                    ->label(__('Supplier'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('quantity')
                    ->label(__('Qty'))
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('unit_price')
                    ->label(__('Unit price'))
                    ->formatStateUsing(fn (mixed $state): string => '₱'.number_format((float) $state, 2))
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('cost')
                    ->label(__('Cost'))
                    ->formatStateUsing(fn (mixed $state): string => '₱'.number_format((float) $state, 2))
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('reorder_level')
                    ->label(__('Reorder at'))
                    ->placeholder('—')
                    ->alignEnd(),
                TextColumn::make('stock_status')
                    ->label(__('Status'))
                    ->badge()
                    ->getStateUsing(function (PosCanteenInventoryItem $record): string {
                        if ($record->isLowStock()) {
                            return 'low';
                        }

                        if (! $record->is_active) {
                            return 'inactive';
                        }

                        return 'ok';
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => __('Low stock'),
                        'inactive' => __('Inactive'),
                        default => __('OK'),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'warning',
                        'inactive' => 'gray',
                        default => 'success',
                    }),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->paginated(false)
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
            'index' => ManageCanteenInventoryItems::route('/'),
        ];
    }
}
