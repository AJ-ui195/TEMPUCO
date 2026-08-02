<?php

namespace App\Filament\Cashier\Concerns;

use App\Enums\PosSaleChannel;
use App\Models\PosCanteenInventoryItem;
use App\Models\PosInventoryItem;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\User;
use App\Support\CanteenBarcodeLookup;
use App\Support\MemberCreditLimit;
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

    public ?string $memberScanFeedback = null;

    public bool $memberScanFeedbackIsError = false;

    public ?string $scanFeedback = null;

    public bool $scanFeedbackIsError = false;

    public bool $showReceiptModal = false;

    public ?int $receiptSaleId = null;

    public bool $showConfirmModal = false;

    public string $pendingConfirmAction = '';

    /** Cart void: tick the lines to pull out before the sale is rung up. */
    public bool $voidMode = false;

    /** @var array<int, int> Product ids ticked for voiding. */
    public array $voidSelection = [];

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
        $this->memberQrInput = '';
        $this->memberSearch = '';
        $this->memberScanFeedback = null;
        $this->memberScanFeedbackIsError = false;
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

    public function getMonthlyCreditLimit(): float
    {
        return MemberCreditLimit::MONTHLY_LIMIT;
    }

    public function getCreditUsedThisMonth(): float
    {
        return $this->memberId === null
            ? 0.0
            : MemberCreditLimit::usedThisMonth($this->memberId, $this->getSaleChannel());
    }

    public function getCreditRemainingThisMonth(): float
    {
        return $this->memberId === null
            ? MemberCreditLimit::MONTHLY_LIMIT
            : MemberCreditLimit::remainingThisMonth($this->memberId, $this->getSaleChannel());
    }

    /** Portion of the cart that would go on the member's account. */
    public function getCreditPortion(): float
    {
        return round(max(0, $this->getCartTotal() - max(0, (float) $this->amountPaid)), 2);
    }

    /** Cash to collect now so the credit portion fits the monthly limit. */
    public function getMinimumCashDue(): float
    {
        return MemberCreditLimit::minimumCashDue(
            $this->getCartTotal(),
            $this->getCreditRemainingThisMonth(),
        );
    }

    public function exceedsMonthlyCreditLimit(): bool
    {
        if (! $this->isCreditSale() || $this->memberId === null || $this->cartLines === []) {
            return false;
        }

        return ! MemberCreditLimit::allows(
            $this->getCreditPortion(),
            $this->getCreditRemainingThisMonth(),
        );
    }

    public function useMinimumCashDue(): void
    {
        $this->amountPaid = (string) $this->getMinimumCashDue();
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
        $this->exitVoidMode();
    }

    public function toggleVoidMode(): void
    {
        $this->voidMode = ! $this->voidMode;
        $this->voidSelection = [];
    }

    public function exitVoidMode(): void
    {
        $this->voidMode = false;
        $this->voidSelection = [];
    }

    public function toggleVoidLine(int $productId): void
    {
        if (! isset($this->cartLines[$productId])) {
            return;
        }

        $this->voidSelection = in_array($productId, $this->voidSelection, true)
            ? array_values(array_diff($this->voidSelection, [$productId]))
            : [...$this->voidSelection, $productId];
    }

    public function isVoidSelected(int $productId): bool
    {
        return in_array($productId, $this->voidSelection, true);
    }

    public function selectAllForVoid(): void
    {
        $this->voidSelection = array_map('intval', array_keys($this->cartLines));
    }

    public function clearVoidSelection(): void
    {
        $this->voidSelection = [];
    }

    public function getVoidSelectionCount(): int
    {
        return count($this->voidSelection);
    }

    public function voidSelectedLines(): void
    {
        $voided = 0;

        foreach ($this->voidSelection as $productId) {
            if (isset($this->cartLines[$productId])) {
                unset($this->cartLines[$productId]);
                $voided++;
            }
        }

        $this->exitVoidMode();

        if ($voided === 0) {
            return;
        }

        if ($this->cartLines === []) {
            $this->amountPaid = '';
        }

        Notification::make()
            ->title(trans_choice(':count item voided|:count items voided', $voided, ['count' => $voided]))
            ->success()
            ->send();
    }

    public function voidAllLines(): void
    {
        $voided = count($this->cartLines);

        $this->cartLines = [];
        $this->amountPaid = '';
        $this->exitVoidMode();

        if ($voided === 0) {
            return;
        }

        Notification::make()
            ->title(__('Cart voided'))
            ->body(trans_choice(':count item removed from the sale.|:count items removed from the sale.', $voided, ['count' => $voided]))
            ->success()
            ->send();
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

            $remaining = MemberCreditLimit::remainingThisMonth($this->memberId, $this->getSaleChannel());

            if (! MemberCreditLimit::allows(round($total - max(0, $paid), 2), $remaining)) {
                Notification::make()
                    ->title($remaining > 0
                        ? __('Monthly credit limit reached')
                        : __('No credit left this month'))
                    ->body($remaining > 0
                        ? __(':member has ₱:remaining left of the ₱:limit monthly limit. Collect at least ₱:cash in cash to complete this sale.', [
                            'member' => $this->memberName,
                            'remaining' => number_format($remaining, 2),
                            'limit' => number_format(MemberCreditLimit::MONTHLY_LIMIT, 2),
                            'cash' => number_format(MemberCreditLimit::minimumCashDue($total, $remaining), 2),
                        ])
                        : __(':member has used the full ₱:limit monthly limit. This sale must be paid in cash.', [
                            'member' => $this->memberName,
                            'limit' => number_format(MemberCreditLimit::MONTHLY_LIMIT, 2),
                        ]))
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

        $sale = DB::transaction(function () use ($memberId, $isCredit, $total, $paid, $saleChannel, $inventoryForeignKey, $catalogModel): PosSale {
            $sale = PosSale::query()->create([
                'pos_branch_id' => null,
                'member_id' => $memberId,
                'cashier_id' => auth()->id(),
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

            return $sale;
        });

        if ($isCredit) {
            $outstanding = round($total - $paid, 2);
            Notification::make()
                ->title(__('Charged to member account'))
                ->body($paid > 0
                    ? __(':member — outstanding: ₱:amount', [
                        'member' => $this->memberName,
                        'amount' => number_format($outstanding, 2),
                    ])
                    : __(':member — full amount on credit: ₱:amount', [
                        'member' => $this->memberName,
                        'amount' => number_format($outstanding, 2),
                    ]))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('Sale completed'))
                ->body(__('Change: ₱:change', ['change' => number_format($paid - $total, 2)]))
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
            'void_selected' => $this->voidSelectedLines(),
            'void_all' => $this->voidAllLines(),
            default => null,
        };
    }

    public function getConfirmModalTitle(): string
    {
        return match ($this->pendingConfirmAction) {
            'complete_sale' => $this->isCreditSale() ? __('Charge to account') : __('Complete sale'),
            'clear_cart' => __('Clear cart'),
            'void_selected' => __('Void selected items'),
            'void_all' => __('Void all items'),
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
            'void_selected' => trans_choice(
                'Void :count selected item from this sale?|Void :count selected items from this sale?',
                $this->getVoidSelectionCount(),
                ['count' => $this->getVoidSelectionCount()],
            ),
            'void_all' => __('Void every item in the cart?'),
            default => '',
        };
    }

    public function getConfirmModalButtonLabel(): string
    {
        return match ($this->pendingConfirmAction) {
            'complete_sale' => $this->isCreditSale() ? __('Charge to account') : __('Complete sale'),
            'clear_cart' => __('Clear cart'),
            'void_selected', 'void_all' => __('Void'),
            default => __('Confirm'),
        };
    }

    public function getReceiptSale(): ?PosSale
    {
        if ($this->receiptSaleId === null) {
            return null;
        }

        return PosSale::query()
            ->with(['items.inventoryItem', 'items.canteenInventoryItem', 'member', 'cashier'])
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
