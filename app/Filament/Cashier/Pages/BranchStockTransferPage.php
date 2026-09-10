<?php

namespace App\Filament\Cashier\Pages;

use App\Models\PosBranch;
use App\Models\PosInventoryItem;
use App\Support\BranchStockTransfer;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BranchStockTransferPage extends Page
{
    protected static ?string $navigationLabel = 'Transfer to branch';

    protected static ?string $title = 'Transfer to branch';

    protected static ?string $slug = 'transfer-to-branch';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 25;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected string $view = 'filament.cashier.branch-stock-transfer';

    public string $branchId = '';

    public string $search = '';

    /** @var array<int, int|string|null> */
    public array $quantities = [];

    /** @var array<int, string|null> */
    public array $expirations = [];

    public function mount(): void
    {
        $this->expirations = PosInventoryItem::query()
            ->active()
            ->where('quantity', '>', 0)
            ->get(['id', 'expiration_date'])
            ->mapWithKeys(fn (PosInventoryItem $item): array => [
                $item->id => $item->expiration_date?->toDateString(),
            ])
            ->all();
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Transfer to branch');
    }

    /**
     * @return Collection<int, PosBranch>
     */
    public function getBranches(): Collection
    {
        return PosBranch::query()
            ->where('is_active', true)
            ->orderBy('branch_number')
            ->get();
    }

    /**
     * @return Collection<int, PosInventoryItem>
     */
    public function getProducts(): Collection
    {
        $term = trim($this->search);

        return PosInventoryItem::query()
            ->active()
            ->where('quantity', '>', 0)
            ->when($term !== '', function ($query) use ($term): void {
                $query->matchingSearch($term);
            })
            ->orderedByName()
            ->get();
    }

    public function getSelectedCount(): int
    {
        return collect($this->quantities)
            ->filter(fn (mixed $quantity): bool => (int) $quantity > 0)
            ->count();
    }

    public function clearQuantities(): void
    {
        $this->quantities = [];
    }

    public function transfer(): void
    {
        if (! filled($this->branchId)) {
            Notification::make()
                ->title(__('Select a branch'))
                ->body(__('Choose the branch that will receive this stock.'))
                ->warning()
                ->send();

            return;
        }

        $items = [];

        foreach ($this->quantities as $itemId => $quantity) {
            $quantity = (int) $quantity;

            if ($quantity < 1) {
                continue;
            }

            $items[] = [
                'pos_inventory_item_id' => (int) $itemId,
                'quantity' => $quantity,
                'expiration_date' => $this->expirations[$itemId] ?? null,
            ];
        }

        if ($items === []) {
            Notification::make()
                ->title(__('No products selected'))
                ->body(__('Enter a quantity on the products you want to transfer.'))
                ->warning()
                ->send();

            return;
        }

        try {
            $transferred = BranchStockTransfer::transferMany((int) $this->branchId, $items);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(__('Transfer failed'))
                ->body(collect($exception->errors())->flatten()->first())
                ->danger()
                ->send();

            return;
        }

        $branch = PosBranch::query()->findOrFail((int) $this->branchId);

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

        $this->quantities = [];
        $this->expirations = [];
    }
}
