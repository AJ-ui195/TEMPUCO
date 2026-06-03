<x-filament-panels::page>
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
                <div style="padding: 1rem 1.25rem; border-radius: 0.75rem; border: 2px solid #0284c7; background: rgba(14, 165, 233, 0.06);">
                    <label for="barcode-scanner" style="display: block; font-size: 0.875rem; font-weight: 700; color: #0284c7; margin-bottom: 0.5rem;">
                        {{ __('Barcode scanner') }}
                    </label>
                    <p style="margin: 0 0 0.75rem; font-size: 0.8125rem; color: #64748b;">
                        {{ __('Scan a product barcode or type the SKU, then press Enter. USB scanners work automatically.') }}
                    </p>
                    <input
                        id="barcode-scanner"
                        type="text"
                        wire:model="barcodeInput"
                        wire:keydown.enter.prevent="scanBarcode"
                        x-ref="barcodeScanner"
                        autocomplete="off"
                        placeholder="{{ __('Scan barcode here…') }}"
                        style="width: 100%; padding: 0.75rem 1rem; font-size: 1.125rem; font-weight: 600; letter-spacing: 0.05em; border-radius: 0.5rem; border: 1px solid #0284c7; box-sizing: border-box;"
                    />
                    @if ($scanFeedback)
                        <p style="margin: 0.75rem 0 0; font-size: 0.8125rem; font-weight: 600; color: {{ $scanFeedbackIsError ? '#b45309' : '#059669' }};">
                            {{ $scanFeedback }}
                        </p>
                    @endif
                </div>

                <div
                    data-pos-no-refocus
                    style="padding: 1rem 1.25rem; border-radius: 0.75rem; border: 1px solid rgba(148, 163, 184, 0.35); background: rgba(255, 255, 255, 0.7);"
                >
                    <label for="product-search" style="display: block; font-size: 0.875rem; font-weight: 700; margin-bottom: 0.5rem;">
                        {{ __('Search products') }}
                    </label>
                    <p style="margin: 0 0 0.75rem; font-size: 0.8125rem; color: #64748b;">
                        {{ __('Search by product name or SKU, then click a result to add to cart.') }}
                    </p>
                    <input
                        id="product-search"
                        type="search"
                        wire:model.live.debounce.300ms="productSearch"
                        autocomplete="off"
                        placeholder="{{ __('Type to search…') }}"
                        style="width: 100%; padding: 0.625rem 0.75rem; font-size: 0.9375rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.5); box-sizing: border-box;"
                    />

                    @php($searchResults = $this->getSearchResults())

                    @if (strlen(trim($productSearch)) >= 2)
                        <div style="margin-top: 0.75rem; max-height: 14rem; overflow-y: auto; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.25);">
                            @forelse ($searchResults as $product)
                                <button
                                    type="button"
                                    wire:click="addProductFromSearch({{ $product->id }})"
                                    style="display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.625rem 0.75rem; text-align: left; border: none; border-bottom: 1px solid rgba(148, 163, 184, 0.15); background: {{ $product->quantity < 1 ? 'rgba(254, 243, 199, 0.25)' : '#fff' }}; cursor: pointer; font: inherit; color: inherit;"
                                >
                                    <span>
                                        <span style="display: block; font-weight: 600; font-size: 0.875rem;">{{ $product->name }}</span>
                                        <span style="font-size: 0.75rem; color: #64748b;">
                                            {{ $product->sku ?? __('No SKU') }}
                                            · {{ __('Stock') }}: {{ number_format($product->quantity) }}
                                        </span>
                                    </span>
                                    <span style="font-weight: 600; color: #0284c7; white-space: nowrap;">
                                        ₱{{ number_format((float) $product->unit_price, 2) }}
                                    </span>
                                </button>
                            @empty
                                <p style="margin: 0; padding: 1rem 0.75rem; font-size: 0.8125rem; color: #64748b; text-align: center;">
                                    {{ __('No products match your search.') }}
                                </p>
                            @endforelse
                        </div>
                    @endif
                </div>

                <div style="border-radius: 0.75rem; border: 1px solid rgba(148, 163, 184, 0.35); overflow: hidden; background: rgba(255, 255, 255, 0.7);">
                    <div style="padding: 0.75rem 1rem; background: rgba(241, 245, 249, 0.9); border-bottom: 1px solid rgba(148, 163, 184, 0.25); display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; font-size: 0.9375rem;">{{ __('Cart') }}</span>
                        <span style="font-size: 0.8125rem; color: #64748b;">
                            {{ trans_choice(':count item|:count items', $this->getCartItemCount(), ['count' => $this->getCartItemCount()]) }}
                        </span>
                    </div>

                    @if ($cartLines === [])
                        <p style="padding: 2rem 1rem; text-align: center; color: #64748b; font-size: 0.875rem; margin: 0;">
                            {{ __('Scan or search products to add them to the cart.') }}
                        </p>
                    @else
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                                <thead>
                                    <tr style="text-align: left; background: rgba(248, 250, 252, 0.9);">
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
                                        <tr style="border-top: 1px solid rgba(148, 163, 184, 0.2);">
                                            <td style="padding: 0.5rem 0.75rem; font-weight: 500;">{{ $line['name'] }}</td>
                                            <td style="padding: 0.5rem 0.75rem; color: #64748b;">{{ $line['sku'] ?? '—' }}</td>
                                            <td style="padding: 0.5rem 0.75rem; text-align: end;">₱{{ number_format($line['unit_price'], 2) }}</td>
                                            <td style="padding: 0.5rem 0.75rem; text-align: center;">
                                                <div style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                                    <button type="button" wire:click="decrementLine({{ $productId }})" style="width: 1.75rem; height: 1.75rem; border-radius: 0.25rem; border: 1px solid #cbd5e1; background: #fff; cursor: pointer;">−</button>
                                                    <span style="min-width: 1.5rem; font-weight: 600;">{{ $line['quantity'] }}</span>
                                                    <button type="button" wire:click="incrementLine({{ $productId }})" style="width: 1.75rem; height: 1.75rem; border-radius: 0.25rem; border: 1px solid #cbd5e1; background: #fff; cursor: pointer;">+</button>
                                                </div>
                                            </td>
                                            <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 600;">₱{{ number_format($line['line_total'], 2) }}</td>
                                            <td style="padding: 0.5rem 0.75rem;">
                                                <button type="button" wire:click="removeLine({{ $productId }})" style="color: #b45309; font-size: 0.75rem; font-weight: 600; background: none; border: none; cursor: pointer;">
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
            <div data-pos-no-refocus style="position: sticky; top: 1rem; padding: 1.25rem; border-radius: 0.75rem; border: 1px solid rgba(148, 163, 184, 0.35); background: rgba(255, 255, 255, 0.85);">
                <h2 style="margin: 0 0 1rem; font-size: 1.125rem; font-weight: 700;">{{ __('Checkout') }}</h2>

                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9375rem;">
                    <span>{{ __('Subtotal') }}</span>
                    <span style="font-weight: 700;">₱{{ number_format($this->getCartTotal(), 2) }}</span>
                </div>

                <div style="margin: 1rem 0; padding-top: 1rem; border-top: 1px solid rgba(148, 163, 184, 0.3);">
                    <label for="amount-paid" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.375rem;">
                        {{ __('Amount paid (₱)') }}
                    </label>
                    <input
                        id="amount-paid"
                        type="number"
                        min="0"
                        step="0.01"
                        wire:model.live="amountPaid"
                        placeholder="0.00"
                        style="width: 100%; padding: 0.625rem 0.75rem; font-size: 1rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.5); box-sizing: border-box;"
                    />
                </div>

                @if ((float) $amountPaid > 0 && $this->getChangeAmount() > 0)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.9375rem; color: #059669;">
                        <span>{{ __('Change') }}</span>
                        <span style="font-weight: 700;">₱{{ number_format($this->getChangeAmount(), 2) }}</span>
                    </div>
                @endif

                <button
                    type="button"
                    wire:click="completeSale"
                    wire:confirm="{{ __('Complete this sale and update stock?') }}"
                    @disabled($cartLines === [])
                    style="width: 100%; padding: 0.75rem; font-size: 0.9375rem; font-weight: 700; color: #fff; background: #0284c7; border: none; border-radius: 0.5rem; cursor: pointer; margin-bottom: 0.5rem; opacity: {{ $cartLines === [] ? '0.5' : '1' }};"
                >
                    {{ __('Complete sale') }}
                </button>

                <button
                    type="button"
                    wire:click="clearCart"
                    wire:confirm="{{ __('Clear all items from the cart?') }}"
                    @disabled($cartLines === [])
                    style="width: 100%; padding: 0.625rem; font-size: 0.8125rem; font-weight: 600; color: #64748b; background: transparent; border: 1px solid rgba(148, 163, 184, 0.5); border-radius: 0.5rem; cursor: pointer;"
                >
                    {{ __('Clear cart') }}
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
