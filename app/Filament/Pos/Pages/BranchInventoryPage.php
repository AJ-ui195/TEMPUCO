<?php

namespace App\Filament\Pos\Pages;

use App\Filament\Pos\Concerns\InteractsWithInventoryPanel;
use App\Models\PosBranch;
use App\Models\PosBranchInventory;
use App\Models\PosInventoryItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class BranchInventoryPage extends Page implements HasTable
{
    use InteractsWithInventoryPanel;
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'branches/{branch}/inventory';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public ?PosBranch $branchRecord = null;

    public function mount(int|string $branch): void
    {
        $this->branchRecord = PosBranch::query()->findOrFail($branch);
        $this->branchRecord->ensureInventoryPivotRecords();
    }

    public function getTitle(): string|Htmlable
    {
        return __('Inventory — :name', ['name' => $this->branchRecord->name]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToBranches')
                ->label(__('Back to branches'))
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(static::inventoryPageUrl(BranchesPage::class)),
            $this->addBranchProductAction(),
        ];
    }

    public function addBranchProductAction(): Action
    {
        return Action::make('addProduct')
            ->label(__('Add product'))
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading(__('Add product to branch'))
            ->form([
                Select::make('pos_inventory_item_id')
                    ->label(__('Product'))
                    ->options(fn (): array => PosInventoryItem::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required()
                    ->native(false),
                TextInput::make('quantity')
                    ->label(__('Quantity'))
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
            ])
            ->action(function (array $data): void {
                $itemId = (int) $data['pos_inventory_item_id'];
                $quantity = (int) $data['quantity'];

                PosBranchInventory::query()->updateOrCreate(
                    [
                        'pos_branch_id' => $this->branchRecord->id,
                        'pos_inventory_item_id' => $itemId,
                    ],
                    ['quantity' => $quantity],
                );

                Notification::make()
                    ->title(__('Product added to branch inventory'))
                    ->success()
                    ->send();

                $this->resetTable();
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->columns([
                TextColumn::make('inventoryItem.name')
                    ->label(__('Product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('inventoryItem.sku')
                    ->label(__('SKU'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('inventoryItem.unit_price')
                    ->label(__('Price'))
                    ->formatStateUsing(fn (mixed $state): string => '₱'.number_format((float) $state, 2))
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('quantity')
                    ->label(__('Qty'))
                    ->sortable()
                    ->alignEnd()
                    ->numeric(),
                TextColumn::make('inventoryItem.reorder_level')
                    ->label(__('Reorder at'))
                    ->placeholder('—')
                    ->alignEnd(),
                TextColumn::make('stock_status')
                    ->label(__('Status'))
                    ->badge()
                    ->getStateUsing(function (PosBranchInventory $record): string {
                        if ($record->inventoryItem->isLowStockAtBranch($record->quantity)) {
                            return 'low';
                        }

                        if (! $record->inventoryItem->is_active) {
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
            ])
            ->defaultSort('id')
            ->recordActions([
                EditAction::make()
                    ->label(__('Edit'))
                    ->modalHeading(fn (PosBranchInventory $record): string => __('Edit — :product', [
                        'product' => $record->inventoryItem->name,
                    ]))
                    ->form([
                        TextInput::make('quantity')
                            ->label(__('Quantity'))
                            ->required()
                            ->integer()
                            ->minValue(0),
                    ])
                    ->after(fn () => $this->resetTable()),
            ])
            ->emptyStateHeading(__('No products in this branch'))
            ->emptyStateDescription(__('Add a product from the catalog using the Add product button above.'))
            ->emptyStateActions([
                $this->addBranchProductAction(),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return PosBranchInventory::query()
            ->where('pos_branch_id', $this->branchRecord->id)
            ->with(['inventoryItem']);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading($this->branchRecord->name)
                    ->description(__('Branch :number · :code', [
                        'number' => $this->branchRecord->branch_number,
                        'code' => $this->branchRecord->code,
                    ]))
                    ->schema([
                        EmbeddedTable::make(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
