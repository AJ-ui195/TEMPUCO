<?php

namespace App\Filament\Cashier\Pages;

use App\Models\PosBranch;
use App\Models\PosInventoryItem;
use App\Support\BranchStockTransfer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class BranchStockTransferPage extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected static ?string $navigationLabel = 'Transfer to branch';

    protected static ?string $title = 'Transfer to branch';

    protected static ?string $slug = 'transfer-to-branch';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 25;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Transfer to branch');
    }

    public function mount(): void
    {
        $this->form->fill([
            'items' => [
                [
                    'pos_inventory_item_id' => null,
                    'quantity' => 1,
                ],
            ],
        ]);
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
                Section::make(__('Pull stock from warehouse'))
                    ->description(__('Move units from grocery warehouse inventory into a branch.'))
                    ->schema([
                        Select::make('pos_branch_id')
                            ->label(__('Branch'))
                            ->options(fn (): array => PosBranch::query()
                                ->where('is_active', true)
                                ->orderBy('branch_number')
                                ->get()
                                ->mapWithKeys(fn (PosBranch $branch): array => [
                                    $branch->id => __('Branch :number — :name', [
                                        'number' => $branch->branch_number,
                                        'name' => $branch->name,
                                    ]),
                                ])
                                ->all())
                            ->searchable()
                            ->required()
                            ->native(false),
                        Repeater::make('items')
                            ->label(__('Products'))
                            ->schema([
                                Select::make('pos_inventory_item_id')
                                    ->label(__('Product'))
                                    ->options(fn (): array => PosInventoryItem::query()
                                        ->active()
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
                                    ->label(__('Quantity'))
                                    ->required()
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->maxValue(function (callable $get): ?int {
                                        $itemId = $get('pos_inventory_item_id');

                                        if (! $itemId) {
                                            return null;
                                        }

                                        return PosInventoryItem::query()
                                            ->whereKey($itemId)
                                            ->value('quantity');
                                    })
                                    ->helperText(function (callable $get): ?string {
                                        $itemId = $get('pos_inventory_item_id');

                                        if (! $itemId) {
                                            return null;
                                        }

                                        $available = PosInventoryItem::query()
                                            ->whereKey($itemId)
                                            ->value('quantity');

                                        if ($available === null) {
                                            return null;
                                        }

                                        return __(':count unit(s) in warehouse.', [
                                            'count' => number_format((int) $available),
                                        ]);
                                    }),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addActionLabel(__('Add product'))
                            ->reorderable(false)
                            ->columns(2)
                            ->columnSpanFull()
                            ->helperText(__('Only products with warehouse stock are listed.')),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('branch-stock-transfer-form')
                    ->livewireSubmitHandler('submit')
                    ->footer([
                        Actions::make([
                            Action::make('submit')
                                ->label(__('Transfer stock'))
                                ->submit('submit'),
                        ]),
                    ]),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $transferred = BranchStockTransfer::transferMany(
            (int) $data['pos_branch_id'],
            $data['items'] ?? [],
        );

        $branch = PosBranch::query()->findOrFail($data['pos_branch_id']);

        $summary = collect($transferred)
            ->map(fn (array $row): string => number_format($row['quantity']).' × '.$row['item']->name)
            ->implode(', ');

        Notification::make()
            ->title(__('Stock transferred'))
            ->body(__('Moved to :branch: :items.', [
                'branch' => $branch->name,
                'items' => $summary,
            ]))
            ->success()
            ->send();

        $this->form->fill([
            'pos_branch_id' => $data['pos_branch_id'],
            'items' => [
                [
                    'pos_inventory_item_id' => null,
                    'quantity' => 1,
                ],
            ],
        ]);
    }
}
