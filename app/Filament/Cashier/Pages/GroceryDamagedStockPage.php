<?php

namespace App\Filament\Cashier\Pages;

use App\Models\PosInventoryDamage;
use App\Models\PosInventoryItem;
use App\Support\RecordInventoryDamage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class GroceryDamagedStockPage extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected static ?string $navigationLabel = 'Damaged stock';

    protected static ?string $title = 'Damaged stock';

    protected static ?string $slug = 'damaged-stock';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxXMark;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Damaged stock');
    }

    public function mount(): void
    {
        $this->resetFormItems();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Pull out damaged stock'))
                    ->description(__('Remove spoiled, expired, or broken units from grocery inventory. Each pull-out is kept on record and feeds the Pull-out column of the close inventory report.'))
                    ->schema([
                        Repeater::make('items')
                            ->label(__('Damaged products'))
                            ->schema([
                                Select::make('pos_inventory_item_id')
                                    ->label(__('Product'))
                                    ->options(fn (): array => PosInventoryItem::query()
                                        ->where('quantity', '>', 0)
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (PosInventoryItem $item): array => [
                                            $item->id => $item->stockQuantityLabel(),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->native(false),
                                TextInput::make('quantity')
                                    ->label(__('Damaged quantity'))
                                    ->required()
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->maxValue(fn (callable $get): ?int => $this->availableStock($get('pos_inventory_item_id')))
                                    ->helperText(function (callable $get): ?string {
                                        $available = $this->availableStock($get('pos_inventory_item_id'));

                                        return $available === null
                                            ? null
                                            : __(':count unit(s) in stock.', ['count' => number_format($available)]);
                                    }),
                                TextInput::make('reason')
                                    ->label(__('Reason'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('e.g. expired, spoiled, broken packaging'))
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addActionLabel(__('Add damaged product'))
                            ->reorderable(false)
                            ->columns(2)
                            ->columnSpanFull()
                            ->helperText(__('Only products with stock on hand are listed.')),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        $recent = $this->recentPullOuts();

        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('grocery-damaged-stock-form')
                    ->livewireSubmitHandler('submit')
                    ->footer([
                        Actions::make([
                            Action::make('submit')
                                ->label(__('Pull out damaged stock'))
                                ->color('danger')
                                ->submit('submit'),
                        ]),
                    ]),
                Section::make(__('Recent pull-outs'))
                    ->description(__('The last 20 damaged-stock entries.'))
                    ->schema([
                        TextEntry::make('recent_damages')
                            ->hiddenLabel()
                            ->state(fn (): HtmlString => new HtmlString(
                                view('filament.pos.damaged-stock-table', [
                                    'damages' => $recent,
                                ])->render()
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $recorded = RecordInventoryDamage::recordMany($data['items'] ?? [], auth()->user());

        $summary = collect($recorded)
            ->map(fn (array $row): string => number_format($row['quantity']).' × '.$row['item']->name)
            ->implode(', ');

        Notification::make()
            ->title(__('Damaged stock pulled out'))
            ->body(__('Removed from inventory: :items.', ['items' => $summary]))
            ->success()
            ->send();

        $this->resetFormItems();
    }

    /**
     * @return Collection<int, PosInventoryDamage>
     */
    protected function recentPullOuts(): Collection
    {
        return PosInventoryDamage::query()
            ->with(['inventoryItem', 'recordedBy'])
            ->latest()
            ->limit(20)
            ->get();
    }

    protected function availableStock(mixed $itemId): ?int
    {
        if (! $itemId) {
            return null;
        }

        $available = PosInventoryItem::query()
            ->whereKey($itemId)
            ->value('quantity');

        return $available === null ? null : (int) $available;
    }

    protected function resetFormItems(): void
    {
        $this->form->fill([
            'items' => [
                [
                    'pos_inventory_item_id' => null,
                    'quantity' => 1,
                    'reason' => null,
                ],
            ],
        ]);
    }
}
