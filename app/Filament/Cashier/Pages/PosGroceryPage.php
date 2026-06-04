<?php

namespace App\Filament\Cashier\Pages;

use App\Models\PosInventoryItem;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Support\PosBarcodeLookup;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosGroceryPage extends BaseDashboard
{
    protected static ?string $title = 'Grocery POS';

    protected static ?string $navigationLabel = 'Grocery POS';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected string $view = 'filament.cashier.pos-grocery';

    public string $barcodeInput = '';

    public string $productSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $cartLines = [];

    public string $amountPaid = '';

    public ?string $scanFeedback = null;

    public bool $scanFeedbackIsError = false;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Grocery POS');
    }

    public function getWidgets(): array
    {
        return [];
    }

    public function scanBarcode(): void
    {
        $code = trim($this->barcodeInput);

        if ($code === '') {
            return;
        }

        $result = PosBarcodeLookup::find($code);

        if (! $result) {
            $this->setScanFeedback(__('No product found for barcode :code.', ['code' => $code]), true);
            $this->barcodeInput = '';

            return;
        }

        $this->addCatalogProduct($result['product']);
        $this->barcodeInput = '';

        $this->dispatch('focus-barcode-scanner');
    }

    /**
     * @return Collection<int, PosInventoryItem>|EloquentCollection<int, PosInventoryItem>
     */
    public function getSearchResults(): Collection|EloquentCollection
    {
        $term = trim($this->productSearch);

        if (strlen($term) < 2) {
            return collect();
        }

        return PosInventoryItem::query()
            ->where('is_active', true)
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    public function addProductFromSearch(int $productId): void
    {
        $product = PosInventoryItem::query()
            ->where('is_active', true)
            ->find($productId);

        if (! $product) {
            $this->setScanFeedback(__('Product not found.'), true);

            return;
        }

        $this->addCatalogProduct($product);
    }

    public function incrementLine(int $productId): void
    {
        if (! isset($this->cartLines[$productId])) {
            return;
        }

        $line = $this->cartLines[$productId];

        if ($line['quantity'] >= $line['max_qty']) {
            $this->setScanFeedback(__('Cannot add more than available stock.'), true);

            return;
        }

        $line['quantity']++;
        $line['line_total'] = round($line['quantity'] * $line['unit_price'], 2);
        $this->cartLines[$productId] = $line;
    }

    public function decrementLine(int $productId): void
    {
        if (! isset($this->cartLines[$productId])) {
            return;
        }

        if ($this->cartLines[$productId]['quantity'] <= 1) {
            unset($this->cartLines[$productId]);

            return;
        }

        $line = $this->cartLines[$productId];
        $line['quantity']--;
        $line['line_total'] = round($line['quantity'] * $line['unit_price'], 2);
        $this->cartLines[$productId] = $line;
    }

    public function removeLine(int $productId): void
    {
        unset($this->cartLines[$productId]);
    }

    public function clearCart(): void
    {
        $this->cartLines = [];
        $this->amountPaid = '';
        $this->scanFeedback = null;
        $this->scanFeedbackIsError = false;
    }

    public function completeSale(): void
    {
        if ($this->cartLines === []) {
            Notification::make()->title(__('Cart is empty'))->warning()->send();

            return;
        }

        $total = $this->getCartTotal();
        $paid = (float) $this->amountPaid;

        if ($paid < $total) {
            Notification::make()
                ->title(__('Payment is less than total'))
                ->body(__('Total: ₱:total', ['total' => number_format($total, 2)]))
                ->danger()
                ->send();

            return;
        }

        $userId = auth()->id();

        DB::transaction(function () use ($userId, $total, $paid): void {
            $sale = PosSale::query()->create([
                'pos_branch_id' => null,
                'user_id' => $userId,
                'total' => $total,
                'amount_paid' => $paid,
                'change_amount' => round($paid - $total, 2),
                'reference' => $this->generateSaleReference(),
            ]);

            foreach ($this->cartLines as $line) {
                PosSaleItem::query()->create([
                    'pos_sale_id' => $sale->id,
                    'pos_inventory_item_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                ]);

                PosInventoryItem::query()
                    ->whereKey($line['product_id'])
                    ->decrement('quantity', $line['quantity']);
            }
        });

        Notification::make()
            ->title(__('Sale completed'))
            ->body(__('Change: ₱:change', ['change' => number_format($paid - $total, 2)]))
            ->success()
            ->send();

        $this->clearCart();
        $this->dispatch('focus-barcode-scanner');
    }

    public function getCartSubtotal(): float
    {
        return $this->getCartTotal();
    }

    public function getCartTotal(): float
    {
        return round(collect($this->cartLines)->sum('line_total'), 2);
    }

    public function getCartItemCount(): int
    {
        return (int) collect($this->cartLines)->sum('quantity');
    }

    public function getChangeAmount(): float
    {
        $paid = (float) $this->amountPaid;
        $total = $this->getCartTotal();

        return $paid > $total ? round($paid - $total, 2) : 0;
    }

    protected function addCatalogProduct(PosInventoryItem $product): void
    {
        $available = (int) $product->quantity;

        if ($available < 1) {
            $this->setScanFeedback(__(':name is out of stock.', ['name' => $product->name]), true);

            return;
        }

        $this->addProductToCart($product, $available);
        $this->setScanFeedback(__('Added :name', ['name' => $product->name]), false);
    }

    protected function addProductToCart(PosInventoryItem $product, int $available): void
    {
        $productId = $product->id;
        $unitPrice = (float) $product->unit_price;

        if (isset($this->cartLines[$productId])) {
            if ($this->cartLines[$productId]['quantity'] >= $available) {
                $this->setScanFeedback(__('Cannot add more than available stock.'), true);

                return;
            }

            $this->cartLines[$productId]['quantity']++;
            $this->cartLines[$productId]['line_total'] = round(
                $this->cartLines[$productId]['quantity'] * $unitPrice,
                2,
            );

            return;
        }

        $this->cartLines[$productId] = [
            'product_id' => $productId,
            'name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => $unitPrice,
            'quantity' => 1,
            'line_total' => round($unitPrice, 2),
            'max_qty' => $available,
        ];
    }

    protected function setScanFeedback(string $message, bool $isError): void
    {
        $this->scanFeedback = $message;
        $this->scanFeedbackIsError = $isError;
    }

    protected function generateSaleReference(): string
    {
        do {
            $reference = 'POS-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (PosSale::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
