<?php

namespace App\Filament\Pos\Pages;

use App\Filament\Cashier\Pages\BranchStockTransferPage;
use App\Filament\Pos\Concerns\InteractsWithInventoryPanel;
use App\Models\PosBranch;
use App\Models\PosBranchInventory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
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
            Action::make('transferStock')
                ->label(__('Transfer to branch'))
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->url(BranchStockTransferPage::getUrl(panel: 'pos')),
        ];
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
                TextColumn::make('expiration_date')
                    ->label(__('Expires'))
                    ->date()
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn (PosBranchInventory $record): ?string => match (true) {
                        $record->isExpired() => 'danger',
                        $record->isExpiringSoon() => 'warning',
                        default => null,
                    }),
                TextColumn::make('created_at')
                    ->label(__('Date added'))
                    ->date()
                    ->sortable(),
                TextColumn::make('inventoryItem.reorder_level')
                    ->label(__('Reorder at'))
                    ->placeholder('—')
                    ->alignEnd(),
                TextColumn::make('stock_status')
                    ->label(__('Status'))
                    ->badge()
                    ->getStateUsing(function (PosBranchInventory $record): string {
                        if ($record->isExpired()) {
                            return 'expired';
                        }

                        if ($record->isExpiringSoon()) {
                            return 'expiring';
                        }

                        if ($record->inventoryItem->isLowStockAtBranch($record->quantity)) {
                            return 'low';
                        }

                        if (! $record->inventoryItem->is_active) {
                            return 'inactive';
                        }

                        return 'ok';
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'expired' => __('Expired'),
                        'expiring' => __('Expiring soon'),
                        'low' => __('Low stock'),
                        'inactive' => __('Inactive'),
                        default => __('OK'),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'expired' => 'danger',
                        'expiring', 'low' => 'warning',
                        'inactive' => 'gray',
                        default => 'success',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
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
                        DatePicker::make('expiration_date')
                            ->label(__('Expiration date'))
                            ->native(false)
                            ->helperText(__('Cashiers are notified 2 weeks before this date. Leave blank if this stock has no expiry.')),
                    ])
                    ->after(fn () => $this->resetTable()),
            ])
            ->emptyStateHeading(__('No transferred products'))
            ->emptyStateDescription(__('Products appear here after you transfer stock from warehouse inventory.'))
            ->emptyStateActions([
                Action::make('transferStock')
                    ->label(__('Transfer to branch'))
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->url(BranchStockTransferPage::getUrl(panel: 'pos')),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return PosBranchInventory::query()
            ->where('pos_branch_id', $this->branchRecord->id)
            ->transferred()
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
