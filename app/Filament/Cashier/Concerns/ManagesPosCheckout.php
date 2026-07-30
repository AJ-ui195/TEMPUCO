<?php

namespace App\Filament\Cashier\Concerns;

use App\Enums\PosSaleChannel;
use App\Models\PosCanteenInventoryItem;
use App\Models\PosInventoryItem;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\User;
use App\Support\CanteenBarcodeLookup;
use App\Support\MemberLoyaltyPoints;
use App\Support\MemberQrCodeLookup;
use App\Support\PhilippineTime;
use App\Support\PosBarcodeLookup;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait ManagesPosCheckout
{
    public string $barcodeInput = '';

    public string $productSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $cartLines = [];

    public string $amountPaid = '';

    /** @var 'cash'|'credit' */
    public string $paymentType = 'cash';

    public string $memberQrInput = '';

    public string $memberSearch = '';

    public ?int $memberId = null;

    public ?string $memberName = null;

    public ?string $memberEmail = null;

    public int $memberPoints = 0;

    public ?string $memberScanFeedback = null;

    public bool $memberScanFeedbackIsError = false;

    public ?string $scanFeedback = null;

    public bool $scanFeedbackIsError = false;

    public bool $showReceiptModal = false;

    public ?int $receiptSaleId = null;

    public bool $showConfirmModal = false;

    public string $pendingConfirmAction = '';

    abstract protected function getSaleChannel(): PosSaleChannel;

    public function scanBarcode(): void
    {
        $code = trim($this->barcodeInput);

        if ($code === '') {
            return;
        }

        if ($this->tryAssignMemberFromScan($code)) {
            $this->barcodeInput = '';
            $this->dispatch('focus-barcode-scanner');

            return;
        }

        $result = $this->lookupBarcodeProduct($code);

        if (! $result) {
            $this->setScanFeedback(__('No product found for barcode :code.', ['code' => $code]), true);
            $this->barcodeInput = '';

            return;
        }

        $this->addCatalogProduct($result['product']);
        $this->barcodeInput = '';

        $this->dispatch('focus-barcode-scanner');
    }

    public function updatedMemberQrInput(string $value): void
    {
        $code = trim($value);

        if ($code === '' || ! str_contains($code, '{')) {
            return;
        }

        if (! str_ends_with($code, '}')) {
            return;
        }

        $this->processMemberQrScan($code);
    }

    public function scanMemberQr(): void
    {
        $this->processMemberQrScan(trim($this->memberQrInput));
    }

    public function clearMember(): void
    {
        $this->memberId = null;
        $this->memberName = null;
        $this->memberEmail = null;
        $this->memberPoints = 0;
        $this->memberQrInput = '';
        $this->memberSearch = '';
        $this->memberScanFeedback = null;
        $this->memberScanFeedbackIsError = false;
    }

    public function willEarnLoyaltyPoint(): bool
    {
        return $this->memberId !== null
            && MemberLoyaltyPoints::qualifies($this->getCartTotal());
    }

    /**
     * @return Collection<int, User>|EloquentCollection<int, User>
     */
    public function getMemberSearchResults(): Collection|EloquentCollection
    {
        $term = trim($this->memberSearch);

        if (strlen($term) < 2) {
            return collect();
        }

        return User::query()
            ->members()
            ->matchingSearch($term)
            ->orderedByName()
            ->limit(15)
            ->get();
    }

    public function selectMember(int $userId): void
    {
        $member = User::query()
            ->members()
            ->find($userId);

        if (! $member instanceof User) {
            $this->setMemberScanFeedback(__('Member not found.'), true);

            return;
        }

        $this->assignMember($member);
        $this->setMemberScanFeedback(__('Member selected. Verify the name below.'), false);
        $this->setScanFeedback(__('Member: :name', ['name' => $member->name]), false);
    }

    public function isCreditSale(): bool
    {
        return $this->paymentType === 'credit';
    }

    /**
     * @return Collection<int, PosInventoryItem|PosCanteenInventoryItem>|EloquentCollection<int, PosInventoryItem|PosCanteenInventoryItem>
     */
    public function getSearchResults(): Collection|EloquentCollection
    {
        $term = trim($this->productSearch);

        if (strlen($term) < 2) {
            return collect();
        }

        return $this->catalogModelClass()::query()
            ->active()
            ->matchingSearch($term)
            ->orderedByName()
            ->limit(20)
            ->get();
    }

    public function addProductFromSearch(int $productId): void
    {
        $product = $this->catalogModelClass()::query()
            ->active()
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
        $this->clearMember();
        $this->paymentType = 'cash';
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

        if ($this->isCreditSale()) {
            if ($this->memberId === null) {
                Notification::make()
                    ->title(__('Member required'))
                    ->body(__('Scan the member QR code before charging to account.'))
                    ->warning()
                    ->send();

                return;
            }

            if ($paid > $total) {
                Notification::make()
                    ->title(__('Payment exceeds total'))
                    ->body(__('Total: ₱:total', ['total' => number_format($total, 2)]))
                    ->danger()
                    ->send();

                return;
            }
        } elseif ($paid < $total) {
            Notification::make()
                ->title(__('Payment is less than total'))
                ->body(__('Total: ₱:total', ['total' => number_format($total, 2)]))
                ->danger()
                ->send();

            return;
        }

        $memberId = $this->memberId;
        $isCredit = $this->isCreditSale();
        $saleChannel = $this->getSaleChannel();
        $inventoryForeignKey = $this->saleItemInventoryForeignKey();
        $catalogModel = $this->catalogModelClass();

        $pointsAwarded = 0;

        $sale = DB::transaction(function () use ($memberId, $isCredit, $total, $paid, $saleChannel, $inventoryForeignKey, $catalogModel, &$pointsAwarded): PosSale {
            $sale = PosSale::query()->create([
                'pos_branch_id' => null,
                'user_id' => $isCredit ? $memberId : auth()->id(),
                'sale_channel' => $saleChannel,
                'total' => $total,
                'amount_paid' => $paid,
                'change_amount' => $isCredit ? 0 : round(max(0, $paid - $total), 2),
                'reference' => $this->generateSaleReference(),
            ]);

            foreach ($this->cartLines as $line) {
                PosSaleItem::query()->create([
                    'pos_sale_id' => $sale->id,
                    $inventoryForeignKey => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                ]);

                $catalogModel::query()
                    ->whereKey($line['product_id'])
                    ->decrement('quantity', $line['quantity']);
            }

            $pointsAwarded = MemberLoyaltyPoints::awardIfEligible($memberId, $total);

            return $sale;
        });

        $pointsNote = $pointsAwarded > 0
            ? ' '.__('+1 loyalty point earned.')
            : '';

        if ($isCredit) {
            $outstanding = round($total - $paid, 2);
            Notification::make()
                ->title(__('Charged to member account'))
                ->body(($paid > 0
                    ? __(':member — outstanding: ₱:amount', [
                        'member' => $this->memberName,
                        'amount' => number_format($outstanding, 2),
                    ])
                    : __(':member — full amount on credit: ₱:amount', [
                        'member' => $this->memberName,
                        'amount' => number_format($outstanding, 2),
                    ])).$pointsNote)
                ->success()
                ->send();
        } else {
            $body = __('Change: ₱:change', ['change' => number_format($paid - $total, 2)]);

            if ($pointsAwarded > 0) {
                $body .= $pointsNote;
            }

            Notification::make()
                ->title(__('Sale completed'))
                ->body($body)
                ->success()
                ->send();
        }

        $this->clearCart();
        $this->receiptSaleId = $sale->id;
        $this->showReceiptModal = true;
    }

    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
        $this->receiptSaleId = null;
        $this->dispatch('focus-barcode-scanner');
    }

    public function openConfirmModal(string $action): void
    {
        $this->pendingConfirmAction = $action;
        $this->showConfirmModal = true;
    }

    public function closeConfirmModal(): void
    {
        $this->showConfirmModal = false;
        $this->pendingConfirmAction = '';
        $this->dispatch('focus-barcode-scanner');
    }

    public function confirmPendingAction(): void
    {
        $action = $this->pendingConfirmAction;
        $this->closeConfirmModal();

        match ($action) {
            'complete_sale' => $this->completeSale(),
            'clear_cart' => $this->clearCart(),
            default => null,
        };
    }

    public function getConfirmModalTitle(): string
    {
        return match ($this->pendingConfirmAction) {
            'complete_sale' => $this->isCreditSale() ? __('Charge to account') : __('Complete sale'),
            'clear_cart' => __('Clear cart'),
            default => __('Confirm'),
        };
    }

    public function getConfirmModalMessage(): string
    {
        return match ($this->pendingConfirmAction) {
            'complete_sale' => $this->isCreditSale()
                ? __('Charge this sale to the member account and update stock?')
                : __('Complete this sale and update stock?'),
            'clear_cart' => __('Clear all items from the cart?'),
            default => '',
        };
    }

    public function getConfirmModalButtonLabel(): string
    {
        return match ($this->pendingConfirmAction) {
            'complete_sale' => $this->isCreditSale() ? __('Charge to account') : __('Complete sale'),
            'clear_cart' => __('Clear cart'),
            default => __('Confirm'),
        };
    }

    public function getReceiptSale(): ?PosSale
    {
        if ($this->receiptSaleId === null) {
            return null;
        }

        return PosSale::query()
            ->with(['items.inventoryItem', 'items.canteenInventoryItem', 'user'])
            ->find($this->receiptSaleId);
    }

    public function getCartSubtotal(): float
    {
        return $this->getCartTotal();
    }

    public function getCartTotal(): float
    {
        return round(collect($this->cartLines)->sum(
            fn (array $line): float => (float) ($line['line_total'] ?? 0),
        ), 2);
    }

    public function getCartItemCount(): int
    {
        return (int) collect($this->cartLines)->sum(
            fn (array $line): int => (int) ($line['quantity'] ?? 0),
        );
    }

    public function getChangeAmount(): float
    {
        $paid = (float) $this->amountPaid;
        $total = $this->getCartTotal();

        return $paid > $total ? round($paid - $total, 2) : 0;
    }

    protected function addCatalogProduct(PosInventoryItem|PosCanteenInventoryItem $product): void
    {
        $available = (int) $product->quantity;

        if ($available < 1) {
            $this->setScanFeedback(__(':name is out of stock.', ['name' => $product->name]), true);

            return;
        }

        $this->addProductToCart($product, $available);
        $this->setScanFeedback(__('Added :name', ['name' => $product->name]), false);
    }

    protected function addProductToCart(PosInventoryItem|PosCanteenInventoryItem $product, int $available): void
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
            $reference = 'POS-'.PhilippineTime::now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (PosSale::referenceExists($reference));

        return $reference;
    }

    protected function processMemberQrScan(string $code): void
    {
        if ($code === '') {
            return;
        }

        if ($this->tryAssignMemberFromScan($code)) {
            $this->memberQrInput = '';

            return;
        }

        $this->setMemberScanFeedback(__('Invalid or unrecognized member QR code.'), true);
        $this->setScanFeedback(__('Invalid or unrecognized member QR code.'), true);
    }

    protected function tryAssignMemberFromScan(string $code): bool
    {
        $member = MemberQrCodeLookup::resolveFromScan($code);

        if (! $member instanceof User) {
            return false;
        }

        $this->assignMember($member);
        $this->setMemberScanFeedback(__('Member identified. Verify the name below.'), false);
        $this->setScanFeedback(__('Member: :name', ['name' => $member->name]), false);

        return true;
    }

    protected function assignMember(User $member): void
    {
        $this->memberId = $member->id;
        $this->memberName = $member->name;
        $this->memberEmail = $member->email;
        $this->memberPoints = (int) $member->points;
        $this->memberSearch = '';
        $this->memberQrInput = '';
    }

    protected function setMemberScanFeedback(string $message, bool $isError): void
    {
        $this->memberScanFeedback = $message;
        $this->memberScanFeedbackIsError = $isError;
    }

    /**
     * @return class-string<PosInventoryItem|PosCanteenInventoryItem>
     */
    protected function catalogModelClass(): string
    {
        return $this->getSaleChannel() === PosSaleChannel::Canteen
            ? PosCanteenInventoryItem::class
            : PosInventoryItem::class;
    }

    protected function saleItemInventoryForeignKey(): string
    {
        return $this->getSaleChannel() === PosSaleChannel::Canteen
            ? 'pos_canteen_inventory_item_id'
            : 'pos_inventory_item_id';
    }

    /**
     * @return array{product: PosInventoryItem|PosCanteenInventoryItem, available_quantity: int}|null
     */
    protected function lookupBarcodeProduct(string $code): ?array
    {
        return $this->getSaleChannel() === PosSaleChannel::Canteen
            ? CanteenBarcodeLookup::find($code)
            : PosBarcodeLookup::find($code);
    }
}
