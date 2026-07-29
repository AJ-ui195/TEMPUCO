<x-filament-panels::page>
    @once
        @push('styles')
            <style>
                .fi-pos-grocery {
                    color: rgb(15 23 42);
                }

                .dark .fi-pos-grocery {
                    color: rgb(255 255 255);
                }

                .fi-pos-grocery .pos-panel {
                    border-radius: 0.75rem;
                    border: 1px solid rgb(226 232 240);
                    background: rgb(255 255 255);
                    color: inherit;
                }

                .dark .fi-pos-grocery .pos-panel {
                    border-color: rgba(255, 255, 255, 0.1);
                    background: rgb(17 24 39);
                }

                .fi-pos-grocery .pos-panel--scanner {
                    border-width: 2px;
                    border-color: rgb(2 132 199);
                    background: rgb(240 249 255);
                }

                .dark .fi-pos-grocery .pos-panel--scanner {
                    border-color: rgb(14 165 233);
                    background: rgba(12, 74, 110, 0.35);
                }

                .fi-pos-grocery .pos-panel--member {
                    border-width: 2px;
                    border-color: rgb(5 150 105);
                    background: rgb(236 253 245);
                }

                .dark .fi-pos-grocery .pos-panel--member {
                    border-color: rgb(16 185 129);
                    background: rgba(6, 78, 59, 0.35);
                }

                .fi-pos-grocery .pos-label-member {
                    color: rgb(4 120 87);
                }

                .dark .fi-pos-grocery .pos-label-member {
                    color: rgb(110 231 183);
                }

                .fi-pos-grocery .pos-payment-option {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    padding: 0.5rem 0.75rem;
                    border-radius: 0.5rem;
                    border: 1px solid rgb(203 213 225);
                    cursor: pointer;
                    font-size: 0.875rem;
                    font-weight: 600;
                }

                .dark .fi-pos-grocery .pos-payment-option {
                    border-color: rgb(75 85 99);
                }

                .fi-pos-grocery .pos-payment-option--active {
                    border-color: rgb(2 132 199);
                    background: rgb(240 249 255);
                }

                .dark .fi-pos-grocery .pos-payment-option--active {
                    border-color: rgb(14 165 233);
                    background: rgba(12, 74, 110, 0.35);
                }

                .fi-pos-grocery .pos-member-verified {
                    padding: 0.875rem 1rem;
                    border-radius: 0.5rem;
                    background: rgb(255 255 255);
                    border: 2px solid rgb(16 185 129);
                    margin-bottom: 0.75rem;
                }

                .dark .fi-pos-grocery .pos-member-verified {
                    background: rgb(17 24 39);
                    border-color: rgb(52 211 153);
                }

                .fi-pos-grocery .pos-member-verified__label {
                    font-size: 0.6875rem;
                    font-weight: 700;
                    letter-spacing: 0.06em;
                    text-transform: uppercase;
                    color: rgb(4 120 87);
                }

                .dark .fi-pos-grocery .pos-member-verified__label {
                    color: rgb(110 231 183);
                }

                .fi-pos-grocery .pos-member-verified__name {
                    display: block;
                    margin-top: 0.375rem;
                    font-size: 1.375rem;
                    font-weight: 700;
                    line-height: 1.25;
                    color: rgb(15 23 42);
                }

                .dark .fi-pos-grocery .pos-member-verified__name {
                    color: rgb(255 255 255);
                }

                .fi-pos-grocery .pos-member-feedback--ok {
                    color: rgb(4 120 87);
                }

                .dark .fi-pos-grocery .pos-member-feedback--ok {
                    color: rgb(110 231 183);
                }

                .fi-pos-grocery .pos-member-feedback--error {
                    color: rgb(220 38 38);
                }

                .dark .fi-pos-grocery .pos-member-feedback--error {
                    color: rgb(252 165 165);
                }

                .fi-pos-grocery .pos-muted {
                    color: rgb(100 116 139);
                }

                .dark .fi-pos-grocery .pos-muted {
                    color: rgb(148 163 184);
                }

                .fi-pos-grocery .pos-label-accent {
                    color: rgb(3 105 161);
                }

                .dark .fi-pos-grocery .pos-label-accent {
                    color: rgb(125 211 252);
                }

                .fi-pos-grocery .pos-cart-header {
                    border-bottom: 1px solid rgb(226 232 240);
                    background: rgb(241 245 249);
                }

                .dark .fi-pos-grocery .pos-cart-header {
                    border-bottom-color: rgba(255, 255, 255, 0.1);
                    background: rgb(31 41 55);
                }

                .fi-pos-grocery .pos-input {
                    width: 100%;
                    box-sizing: border-box;
                    border-radius: 0.5rem;
                    border: 1px solid rgb(203 213 225);
                    background: rgb(255 255 255);
                    color: rgb(15 23 42);
                }

                .dark .fi-pos-grocery .pos-input {
                    border-color: rgb(75 85 99);
                    background: rgb(3 7 18);
                    color: rgb(255 255 255);
                }

                .fi-pos-grocery .pos-input::placeholder {
                    color: rgb(148 163 184);
                }

                .fi-pos-grocery .pos-input--scanner {
                    border-color: rgb(2 132 199);
                    font-size: 1.125rem;
                    font-weight: 600;
                    letter-spacing: 0.05em;
                    padding: 0.75rem 1rem;
                }

                .dark .fi-pos-grocery .pos-input--scanner {
                    border-color: rgb(14 165 233);
                }

                .fi-pos-grocery .pos-search-result {
                    display: flex;
                    width: 100%;
                    cursor: pointer;
                    align-items: center;
                    justify-content: space-between;
                    gap: 0.75rem;
                    padding: 0.625rem 0.75rem;
                    text-align: left;
                    border: none;
                    border-bottom: 1px solid rgb(241 245 249);
                    background: rgb(255 255 255);
                    color: inherit;
                    font: inherit;
                }

                .dark .fi-pos-grocery .pos-search-result {
                    border-bottom-color: rgba(255, 255, 255, 0.05);
                    background: rgb(17 24 39);
                }

                .fi-pos-grocery .pos-search-result:hover {
                    background: rgb(248 250 252);
                }

                .dark .fi-pos-grocery .pos-search-result:hover {
                    background: rgb(31 41 55);
                }

                .fi-pos-grocery .pos-search-result--low-stock {
                    background: rgb(255 251 235);
                }

                .dark .fi-pos-grocery .pos-search-result--low-stock {
                    background: rgba(120, 53, 15, 0.25);
                }

                .fi-pos-grocery .pos-price {
                    color: rgb(2 132 199);
                    font-weight: 600;
                    white-space: nowrap;
                }

                .dark .fi-pos-grocery .pos-price {
                    color: rgb(56 189 248);
                }

                .fi-pos-grocery .pos-table thead tr {
                    background: rgb(248 250 252);
                    color: rgb(51 65 85);
                }

                .dark .fi-pos-grocery .pos-table thead tr {
                    background: rgba(31, 41, 55, 0.8);
                    color: rgb(203 213 225);
                }

                .fi-pos-grocery .pos-table tbody tr {
                    border-top: 1px solid rgb(226 232 240);
                }

                .dark .fi-pos-grocery .pos-table tbody tr {
                    border-top-color: rgba(255, 255, 255, 0.1);
                }

                .fi-pos-grocery .pos-qty-btn {
                    width: 1.75rem;
                    height: 1.75rem;
                    border-radius: 0.25rem;
                    border: 1px solid rgb(203 213 225);
                    background: rgb(255 255 255);
                    color: inherit;
                    cursor: pointer;
                }

                .dark .fi-pos-grocery .pos-qty-btn {
                    border-color: rgb(75 85 99);
                    background: rgb(31 41 55);
                }

                .fi-pos-grocery .pos-btn-primary {
                    width: 100%;
                    padding: 0.75rem;
                    font-size: 0.9375rem;
                    font-weight: 700;
                    color: #fff;
                    background: rgb(2 132 199);
                    border: none;
                    border-radius: 0.5rem;
                    cursor: pointer;
                }

                .dark .fi-pos-grocery .pos-btn-primary {
                    background: rgb(14 165 233);
                }

                .fi-pos-grocery .pos-btn-secondary {
                    width: 100%;
                    padding: 0.625rem;
                    font-size: 0.8125rem;
                    font-weight: 600;
                    color: rgb(100 116 139);
                    background: transparent;
                    border: 1px solid rgb(203 213 225);
                    border-radius: 0.5rem;
                    cursor: pointer;
                }

                .dark .fi-pos-grocery .pos-btn-secondary {
                    color: rgb(148 163 184);
                    border-color: rgb(75 85 99);
                }

                .fi-pos-grocery .pos-btn-remove {
                    color: rgb(180 83 9);
                    font-size: 0.75rem;
                    font-weight: 600;
                    background: none;
                    border: none;
                    cursor: pointer;
                }

                .dark .fi-pos-grocery .pos-btn-remove {
                    color: rgb(251 191 36);
                }

                .fi-pos-grocery .pos-feedback--ok {
                    color: rgb(5 150 105);
                }

                .dark .fi-pos-grocery .pos-feedback--ok {
                    color: rgb(52 211 153);
                }

                .fi-pos-grocery .pos-feedback--error {
                    color: rgb(180 83 9);
                }

                .dark .fi-pos-grocery .pos-feedback--error {
                    color: rgb(251 191 36);
                }

                .fi-pos-grocery .pos-change {
                    color: rgb(5 150 105);
                }

                .dark .fi-pos-grocery .pos-change {
                    color: rgb(52 211 153);
                }

                .fi-pos-grocery .pos-checkout-divider {
                    border-top: 1px solid rgb(226 232 240);
                }

                .dark .fi-pos-grocery .pos-checkout-divider {
                    border-top-color: rgba(255, 255, 255, 0.1);
                }

                .fi-pos-grocery .pos-modal {
                    position: fixed;
                    inset: 0;
                    z-index: 50;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 1rem;
                }

                .fi-pos-grocery .pos-modal__backdrop {
                    position: absolute;
                    inset: 0;
                    background: rgba(15, 23, 42, 0.55);
                }

                .fi-pos-grocery .pos-modal__dialog {
                    position: relative;
                    z-index: 1;
                    width: min(100%, 24rem);
                    max-height: calc(100vh - 2rem);
                    overflow-y: auto;
                    border-radius: 0.75rem;
                    background: #fff;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
                    padding: 1.25rem;
                }

                .dark .fi-pos-grocery .pos-modal__dialog {
                    background: rgb(17 24 39);
                }

                .fi-pos-grocery .pos-modal__title {
                    margin: 0 0 0.75rem;
                    font-family: system-ui, sans-serif;
                    font-size: 1rem;
                    font-weight: 700;
                    text-align: center;
                }

                .fi-pos-grocery .pos-modal__message {
                    margin: 0;
                    font-family: system-ui, sans-serif;
                    font-size: 0.9375rem;
                    line-height: 1.5;
                    text-align: center;
                    color: rgb(71 85 105);
                }

                .dark .fi-pos-grocery .pos-modal__message {
                    color: rgb(148 163 184);
                }

                .fi-pos-grocery .pos-modal__actions {
                    display: flex;
                    gap: 0.5rem;
                    justify-content: center;
                    margin-top: 1.25rem;
                    font-family: system-ui, sans-serif;
                }

                .fi-pos-grocery .pos-receipt-paper {
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #000;
                    padding: 0.75rem;
                    border: 1px solid rgb(226 232 240);
                    border-radius: 0.5rem;
                    background: #fff;
                }

                .dark .fi-pos-grocery .pos-receipt-paper {
                    color: #fff;
                    background: rgb(3 7 18);
                    border-color: rgba(255, 255, 255, 0.1);
                }

                .fi-pos-grocery .pos-receipt-paper__center { text-align: center; }
                .fi-pos-grocery .pos-receipt-paper__brand { font-size: 14px; font-weight: 700; letter-spacing: 0.04em; }
                .fi-pos-grocery .pos-receipt-paper__muted { color: #4b5563; }
                .dark .fi-pos-grocery .pos-receipt-paper__muted { color: rgb(148 163 184); }
                .fi-pos-grocery .pos-receipt-paper__subtitle,
                .fi-pos-grocery .pos-receipt-paper__footer {
                    color: #111827;
                    font-weight: 700;
                    font-size: 12px;
                }
                .dark .fi-pos-grocery .pos-receipt-paper__subtitle,
                .dark .fi-pos-grocery .pos-receipt-paper__footer {
                    color: #f8fafc;
                }
                .fi-pos-grocery .pos-receipt-paper__unit-price {
                    color: #111827;
                    font-weight: 700;
                    font-size: 12px;
                    margin-top: 0.1rem;
                }
                .dark .fi-pos-grocery .pos-receipt-paper__unit-price {
                    color: #f8fafc;
                }
                .fi-pos-grocery .pos-receipt-paper__divider { border-top: 1px dashed #9ca3af; margin: 0.625rem 0; }
                .fi-pos-grocery .pos-receipt-paper__table { width: 100%; border-collapse: collapse; }
                .fi-pos-grocery .pos-receipt-paper__table th,
                .fi-pos-grocery .pos-receipt-paper__table td { padding: 0.125rem 0; vertical-align: top; }
                .fi-pos-grocery .pos-receipt-paper__table th { font-weight: 700; text-align: left; border-bottom: 1px solid #000; }
                .dark .fi-pos-grocery .pos-receipt-paper__table th { border-bottom-color: rgba(255, 255, 255, 0.25); }
                .fi-pos-grocery .pos-receipt-paper__qty { width: 2rem; text-align: center; }
                .fi-pos-grocery .pos-receipt-paper__amount { text-align: right; white-space: nowrap; }
                .fi-pos-grocery .pos-receipt-paper__totals td { padding-top: 0.25rem; }
                .fi-pos-grocery .pos-receipt-paper__label { text-align: right; padding-right: 0.5rem; }
                .fi-pos-grocery .pos-receipt-paper__grand { font-weight: 700; font-size: 13px; }
            </style>
        @endpush
    @endonce

    <style
        id="pos-receipt-iframe-styles"
        media="not all"
        data-receipt-title="{{ __('Receipt') }}"
    >@include('filament.cashier.partials.receipt-print-styles')</style>

    <div
        class="fi-pos-grocery"
        x-data="{
            focusScanner() {
                const el = this.$refs.barcodeScanner;
                const active = document.activeElement;
                if (! el) return;
                if (active === el) return;
                if (active?.closest?.('[data-pos-no-refocus]')) return;
                el.focus();
            },
        }"
        x-on:click.window="focusScanner()"
        x-on:focus-barcode-scanner.window="focusScanner()"
        x-init="focusScanner()"
    >
        <div style="display: grid; grid-template-columns: 1fr 22rem; gap: 1.25rem; align-items: start;">
            {{-- Left: scanner + cart --}}
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="pos-panel pos-panel--scanner" style="padding: 1rem 1.25rem;">
                    <label for="barcode-scanner" class="pos-label-accent" style="display: block; font-size: 0.875rem; font-weight: 700; margin-bottom: 0.5rem;">
                        {{ __('Barcode scanner') }}
                    </label>
                    <p class="pos-muted" style="margin: 0 0 0.75rem; font-size: 0.8125rem;">
                        {{ __('Scan a product barcode.') }}
                    </p>
                    <input
                        id="barcode-scanner"
                        type="text"
                        wire:model="barcodeInput"
                        wire:keydown.enter.prevent="scanBarcode"
                        x-ref="barcodeScanner"
                        autocomplete="off"
                        placeholder="{{ __('Scan barcode here…') }}"
                        class="pos-input pos-input--scanner"
                    />
                    @if ($scanFeedback)
                        <p class="{{ $scanFeedbackIsError ? 'pos-feedback--error' : 'pos-feedback--ok' }}" style="margin: 0.75rem 0 0; font-size: 0.8125rem; font-weight: 600;">
                            {{ $scanFeedback }}
                        </p>
                    @endif
                </div>

                <div data-pos-no-refocus class="pos-panel" style="padding: 1rem 1.25rem;">
                    <label for="product-search" style="display: block; font-size: 0.875rem; font-weight: 700; margin-bottom: 0.5rem;">
                        {{ __('Search products') }}
                    </label>
                    <p class="pos-muted" style="margin: 0 0 0.75rem; font-size: 0.8125rem;">
                        {{ __('Search by product name or SKU, then click a result to add to cart.') }}
                    </p>
                    <input
                        id="product-search"
                        type="search"
                        wire:model.live.debounce.300ms="productSearch"
                        autocomplete="off"
                        placeholder="{{ __('Type to search…') }}"
                        class="pos-input"
                        style="padding: 0.625rem 0.75rem; font-size: 0.9375rem;"
                    />

                    @php($searchResults = $this->getSearchResults())

                    @if (strlen(trim($productSearch)) >= 2)
                        <div class="pos-panel" style="margin-top: 0.75rem; max-height: 14rem; overflow-y: auto; padding: 0;">
                            @forelse ($searchResults as $product)
                                <button
                                    type="button"
                                    wire:click="addProductFromSearch({{ $product->id }})"
                                    class="pos-search-result {{ $product->quantity < 1 ? 'pos-search-result--low-stock' : '' }}"
                                >
                                    <span>
                                        <span style="display: block; font-weight: 600; font-size: 0.875rem;">{{ $product->name }}</span>
                                        <span class="pos-muted" style="font-size: 0.75rem;">
                                            {{ $product->sku ?? __('No SKU') }}
                                            · {{ __('Stock') }}: {{ number_format($product->quantity) }}
                                        </span>
                                    </span>
                                    <span class="pos-price">
                                        ₱{{ number_format((float) $product->unit_price, 2) }}
                                    </span>
                                </button>
                            @empty
                                <p class="pos-muted" style="margin: 0; padding: 1rem 0.75rem; font-size: 0.8125rem; text-align: center;">
                                    {{ __('No products match your search.') }}
                                </p>
                            @endforelse
                        </div>
                    @endif
                </div>

                <div class="pos-panel" style="padding: 0; overflow: hidden;">
                    <div class="pos-cart-header" style="padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; font-size: 0.9375rem;">{{ __('Cart') }}</span>
                        <span class="pos-muted" style="font-size: 0.8125rem;">
                            {{ trans_choice(':count item|:count items', $this->getCartItemCount(), ['count' => $this->getCartItemCount()]) }}
                        </span>
                    </div>

                    @if ($cartLines === [])
                        <p class="pos-muted" style="padding: 2rem 1rem; text-align: center; font-size: 0.875rem; margin: 0;">
                            {{ __('Scan or search products to add them to the cart.') }}
                        </p>
                    @else
                        <div style="overflow-x: auto;">
                            <table class="pos-table" style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                                <thead>
                                    <tr style="text-align: left;">
                                        <th style="padding: 0.5rem 0.75rem;">{{ __('Product') }}</th>
                                        <th style="padding: 0.5rem 0.75rem;">{{ __('SKU') }}</th>
                                        <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Price') }}</th>
                                        <th style="padding: 0.5rem 0.75rem; text-align: center;">{{ __('Qty') }}</th>
                                        <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Total') }}</th>
                                        <th style="padding: 0.5rem 0.75rem;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cartLines as $productId => $line)
                                        <tr>
                                            <td style="padding: 0.5rem 0.75rem; font-weight: 500;">{{ $line['name'] }}</td>
                                            <td class="pos-muted" style="padding: 0.5rem 0.75rem;">{{ $line['sku'] ?? '—' }}</td>
                                            <td style="padding: 0.5rem 0.75rem; text-align: end;">₱{{ number_format($line['unit_price'], 2) }}</td>
                                            <td style="padding: 0.5rem 0.75rem; text-align: center;">
                                                <div style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                                    <button type="button" wire:click="decrementLine({{ $productId }})" class="pos-qty-btn">−</button>
                                                    <span style="min-width: 1.5rem; font-weight: 600;">{{ $line['quantity'] }}</span>
                                                    <button type="button" wire:click="incrementLine({{ $productId }})" class="pos-qty-btn">+</button>
                                                </div>
                                            </td>
                                            <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 600;">₱{{ number_format($line['line_total'], 2) }}</td>
                                            <td style="padding: 0.5rem 0.75rem;">
                                                <button type="button" wire:click="removeLine({{ $productId }})" class="pos-btn-remove">
                                                    {{ __('Remove') }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right: checkout --}}
            <div data-pos-no-refocus class="pos-panel" style="position: sticky; top: 1rem; padding: 1.25rem;">
                <h2 style="margin: 0 0 1rem; font-size: 1.125rem; font-weight: 700;">{{ __('Checkout') }}</h2>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem;">
                    <label class="pos-payment-option {{ $paymentType === 'cash' ? 'pos-payment-option--active' : '' }}">
                        <input type="radio" wire:model.live="paymentType" value="cash" style="accent-color: #0284c7;" />
                        {{ __('Cash') }}
                    </label>
                    <label class="pos-payment-option {{ $paymentType === 'credit' ? 'pos-payment-option--active' : '' }}">
                        <input type="radio" wire:model.live="paymentType" value="credit" style="accent-color: #0284c7;" />
                        {{ __('Credit') }}
                    </label>
                </div>

                @if ($paymentType === 'credit')
                    <div class="pos-panel pos-panel--member" style="padding: 1rem; margin-bottom: 1rem;">
                        <label class="pos-label-member" style="display: block; font-size: 0.875rem; font-weight: 700; margin-bottom: 0.375rem;">
                            {{ __('Member') }}
                        </label>
                        <p class="pos-muted" style="margin: 0 0 0.625rem; font-size: 0.75rem;">
                            {{ __('Required for credit sales. Scan a QR code or search and select a member.') }}
                        </p>

                        @if ($memberId)
                            <div class="pos-member-verified" style="margin-bottom: 0.75rem;">
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem;">
                                    <div style="min-width: 0;">
                                        <span class="pos-member-verified__label">{{ __('Verify member name') }}</span>
                                        <span class="pos-member-verified__name">{{ $memberName }}</span>
                                        @if ($memberEmail)
                                            <span class="pos-muted" style="display: block; margin-top: 0.375rem; font-size: 0.8125rem;">
                                                {{ $memberEmail }}
                                            </span>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="clearMember"
                                        class="pos-btn-remove"
                                        style="flex-shrink: 0; padding: 0.25rem 0.5rem; font-size: 0.75rem;"
                                    >
                                        {{ __('Clear') }}
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if ($memberScanFeedback)
                            <p
                                class="{{ $memberScanFeedbackIsError ? 'pos-member-feedback--error' : 'pos-member-feedback--ok' }}"
                                style="margin: 0 0 0.625rem; font-size: 0.8125rem; font-weight: 600;"
                            >
                                {{ $memberScanFeedback }}
                            </p>
                        @endif

                        <label for="member-search" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem;">
                            {{ __('Search member') }}
                        </label>
                        <input
                            id="member-search"
                            type="search"
                            wire:model.live.debounce.300ms="memberSearch"
                            autocomplete="off"
                            placeholder="{{ __('Name, email, or phone…') }}"
                            class="pos-input"
                            style="padding: 0.625rem 0.75rem; font-size: 0.9375rem; margin-bottom: 0.625rem;"
                        />

                        @php($memberResults = $this->getMemberSearchResults())

                        @if (strlen(trim($memberSearch)) >= 2)
                            <div class="pos-panel" style="margin-bottom: 0.75rem; max-height: 11rem; overflow-y: auto; padding: 0;">
                                @forelse ($memberResults as $member)
                                    <button
                                        type="button"
                                        wire:click="selectMember({{ $member->id }})"
                                        class="pos-search-result"
                                    >
                                        <span>
                                            <span style="display: block; font-weight: 600; font-size: 0.875rem;">{{ $member->name }}</span>
                                            <span class="pos-muted" style="font-size: 0.75rem;">
                                                {{ $member->email }}
                                                @if ($member->cellphone)
                                                    · {{ $member->cellphone }}
                                                @endif
                                            </span>
                                        </span>
                                    </button>
                                @empty
                                    <p class="pos-muted" style="margin: 0; padding: 1rem 0.75rem; font-size: 0.8125rem; text-align: center;">
                                        {{ __('No members match your search.') }}
                                    </p>
                                @endforelse
                            </div>
                        @endif

                        <div class="pos-checkout-divider" style="margin: 0.75rem 0; text-align: center; font-size: 0.75rem; font-weight: 600; color: rgb(100 116 139);">
                            {{ __('or scan QR') }}
                        </div>

                        <input
                            id="member-qr-scanner"
                            type="text"
                            wire:model.live="memberQrInput"
                            wire:keydown.enter.prevent="scanMemberQr"
                            autocomplete="off"
                            placeholder="{{ __('Scan member QR…') }}"
                            class="pos-input"
                            style="padding: 0.625rem 0.75rem; font-size: 0.9375rem;"
                        />
                    </div>
                @endif

                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9375rem;">
                    <span>{{ __('Subtotal') }}</span>
                    <span style="font-weight: 700;">₱{{ number_format($this->getCartTotal(), 2) }}</span>
                </div>

                @if ($paymentType === 'credit' && $memberId && $this->getCartTotal() > 0)
                    @php($creditDue = max(0, $this->getCartTotal() - (float) $amountPaid))
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 0.875rem; color: rgb(4 120 87); font-weight: 600;">
                        <span>{{ __('Amount on account') }}</span>
                        <span>₱{{ number_format($creditDue, 2) }}</span>
                    </div>
                @endif

                <div class="pos-checkout-divider" style="margin: 1rem 0; padding-top: 1rem;">
                    <label for="amount-paid" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.375rem;">
                        @if ($paymentType === 'credit')
                            {{ __('Amount paid now (₱) — optional') }}
                        @else
                            {{ __('Amount paid (₱)') }}
                        @endif
                    </label>
                    <input
                        id="amount-paid"
                        type="number"
                        min="0"
                        step="0.01"
                        wire:model.live="amountPaid"
                        placeholder="{{ $paymentType === 'credit' ? '0.00' : '0.00' }}"
                        class="pos-input"
                        style="padding: 0.625rem 0.75rem; font-size: 1rem;"
                    />
                </div>

                @if ($paymentType === 'cash' && (float) $amountPaid > 0 && $this->getChangeAmount() > 0)
                    <div class="pos-change" style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.9375rem;">
                        <span>{{ __('Change') }}</span>
                        <span style="font-weight: 700;">₱{{ number_format($this->getChangeAmount(), 2) }}</span>
                    </div>
                @endif

                <button
                    type="button"
                    wire:click="openConfirmModal('complete_sale')"
                    @disabled($cartLines === [] || ($paymentType === 'credit' && ! $memberId))
                    class="pos-btn-primary"
                    style="margin-bottom: 0.5rem; opacity: {{ ($cartLines === [] || ($paymentType === 'credit' && ! $memberId)) ? '0.5' : '1' }};"
                >
                    @if ($paymentType === 'credit')
                        {{ __('Charge to account') }}
                    @else
                        {{ __('Complete sale') }}
                    @endif
                </button>

                <button
                    type="button"
                    wire:click="openConfirmModal('clear_cart')"
                    @disabled($cartLines === [])
                    class="pos-btn-secondary"
                    style="opacity: {{ $cartLines === [] ? '0.5' : '1' }};"
                >
                    {{ __('Clear cart') }}
                </button>
            </div>
        </div>

        @if ($showConfirmModal && $pendingConfirmAction !== '')
            <div
                class="pos-modal"
                data-pos-no-refocus
                wire:key="confirm-modal-{{ $pendingConfirmAction }}"
            >
                <div class="pos-modal__backdrop" wire:click="closeConfirmModal"></div>
                <div class="pos-modal__dialog">
                    <h3 class="pos-modal__title">{{ $this->getConfirmModalTitle() }}</h3>
                    <p class="pos-modal__message">{{ $this->getConfirmModalMessage() }}</p>
                    <div class="pos-modal__actions">
                        <button type="button" wire:click="confirmPendingAction" class="pos-btn-primary">
                            {{ $this->getConfirmModalButtonLabel() }}
                        </button>
                        <button type="button" wire:click="closeConfirmModal" class="pos-btn-secondary">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @if ($showReceiptModal && ($receiptSale = $this->getReceiptSale()))
            <div
                class="pos-modal"
                data-pos-no-refocus
                wire:key="receipt-modal-{{ $receiptSaleId }}"
                x-data
                x-init="$nextTick(() => setTimeout(() => window.posGroceryPrintReceipt?.(), 100))"
            >
                <div class="pos-modal__backdrop" wire:click="closeReceiptModal"></div>
                <div class="pos-modal__dialog">
                    <h3 class="pos-modal__title">{{ __('Receipt') }}</h3>
                    <div id="pos-receipt-print-area">
                        @include('filament.cashier.partials.receipt-body', [
                            'sale' => $receiptSale,
                            'cashier' => auth()->user(),
                        ])
                    </div>
                    <div class="pos-modal__actions">
                        <button type="button" onclick="window.posGroceryPrintReceipt()" class="pos-btn-primary">
                            {{ __('Print') }}
                        </button>
                        <button type="button" wire:click="closeReceiptModal" class="pos-btn-secondary">
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script src="{{ asset('js/pos-grocery-print-receipt.js') }}"></script>
</x-filament-panels::page>
