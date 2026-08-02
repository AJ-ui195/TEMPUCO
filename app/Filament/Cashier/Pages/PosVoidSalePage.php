<?php

namespace App\Filament\Cashier\Pages;

use App\Enums\PosSaleChannel;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Support\VoidPosSaleItems;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class PosVoidSalePage extends Page
{
    protected static ?string $navigationLabel = 'Void sale';

    protected static ?string $title = 'Void sale';

    protected static ?string $slug = 'void-sale';

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    protected string $view = 'filament.cashier.pos-void-sale';

    public string $saleSearch = '';

    public ?int $selectedSaleId = null;

    /** @var array<int, int> Sale item ids ticked for voiding. */
    public array $selectedItemIds = [];

    public string $reason = '';

    public ?string $voidError = null;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Void sale');
    }

    /** Cashiers may only void sales rung up on their own register. */
    public function getSaleChannel(): PosSaleChannel
    {
        return Filament::getCurrentPanel()?->getId() === 'pos-canteen'
            ? PosSaleChannel::Canteen
            : PosSaleChannel::Grocery;
    }

    /**
     * @return Collection<int, PosSale>|EloquentCollection<int, PosSale>
     */
    public function getRecentSales(): Collection|EloquentCollection
    {
        $term = trim($this->saleSearch);

        return PosSale::query()
            ->where('sale_channel', $this->getSaleChannel()->value)
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $match) use ($term): void {
                    $match->where('reference', 'like', '%'.$term.'%')
                        ->orWhereHas('member', fn (Builder $member) => $member->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->with(['member', 'cashier'])
            ->withCount('items')
            ->latest()
            ->limit(15)
            ->get();
    }

    public function selectSale(int $saleId): void
    {
        $sale = PosSale::query()
            ->where('sale_channel', $this->getSaleChannel()->value)
            ->find($saleId);

        if (! $sale instanceof PosSale) {
            return;
        }

        $this->selectedSaleId = $sale->id;
        $this->selectedItemIds = [];
        $this->reason = '';
        $this->voidError = null;
        $this->saleSearch = '';
    }

    public function clearSale(): void
    {
        $this->selectedSaleId = null;
        $this->selectedItemIds = [];
        $this->reason = '';
        $this->voidError = null;
    }

    public function getSelectedSale(): ?PosSale
    {
        if ($this->selectedSaleId === null) {
            return null;
        }

        return PosSale::query()
            ->with(['member', 'cashier', 'items.inventoryItem', 'items.canteenInventoryItem', 'items.voidRecord.cashier'])
            ->find($this->selectedSaleId);
    }

    public function toggleItem(int $itemId): void
    {
        $this->selectedItemIds = in_array($itemId, $this->selectedItemIds, true)
            ? array_values(array_diff($this->selectedItemIds, [$itemId]))
            : [...$this->selectedItemIds, $itemId];

        $this->voidError = null;
    }

    public function isItemSelected(int $itemId): bool
    {
        return in_array($itemId, $this->selectedItemIds, true);
    }

    public function selectAllItems(): void
    {
        $sale = $this->getSelectedSale();

        if (! $sale instanceof PosSale) {
            return;
        }

        $this->selectedItemIds = $sale->items
            ->reject(fn (PosSaleItem $item): bool => $item->voidRecord !== null)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function clearItemSelection(): void
    {
        $this->selectedItemIds = [];
    }

    public function voidSelected(): void
    {
        $sale = $this->getSelectedSale();

        if (! $sale instanceof PosSale) {
            return;
        }

        if ($this->selectedItemIds === []) {
            $this->voidError = __('Tick the items you are voiding.');

            return;
        }

        $this->runVoid($sale, $this->selectedItemIds);
    }

    public function voidWholeSale(): void
    {
        $sale = $this->getSelectedSale();

        if (! $sale instanceof PosSale) {
            return;
        }

        $this->runVoid($sale, $sale->activeItems()->pluck('id')->all());
    }

    /**
     * @param  array<int, int>  $itemIds
     */
    protected function runVoid(PosSale $sale, array $itemIds): void
    {
        $reason = trim($this->reason);

        if ($reason === '') {
            $this->voidError = __('Enter the reason for the void.');

            return;
        }

        if ($itemIds === []) {
            $this->voidError = __('Every item on this sale has already been voided.');

            return;
        }

        $result = VoidPosSaleItems::void($sale, $itemIds, $reason, auth()->user());

        if ($result['voided'] === 0) {
            $this->voidError = __('Those items were already voided.');

            return;
        }

        $this->selectedItemIds = [];
        $this->reason = '';
        $this->voidError = null;

        Notification::make()
            ->title($result['fully_voided'] ? __('Sale voided') : __('Items voided'))
            ->body(__(':count item(s) voided · :units unit(s) returned to stock · new sale total: ₱:total', [
                'count' => $result['voided'],
                'units' => $result['restored_units'],
                'total' => number_format($result['new_total'], 2),
            ]))
            ->success()
            ->send();
    }
}
