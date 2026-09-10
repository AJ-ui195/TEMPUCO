<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $branches = $this->getBranches();
        $products = $this->getProducts();
        $selectedCount = $this->getSelectedCount();
    @endphp

    <div class="fi-pos-ui">
        <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
            <h2 style="margin: 0; font-size: 1.125rem; font-weight: 700;">{{ __('Transfer to branch') }}</h2>
            <p class="pos-muted" style="margin: 0.375rem 0 1rem; font-size: 0.8125rem;">
                {{ __('Enter a quantity on each product you want to move. Leave the rest blank. Only products with warehouse stock are listed.') }}
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 0.75rem; align-items: end;">
                <div>
                    <label for="transfer-branch" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem;">
                        {{ __('Branch') }}
                    </label>
                    <select id="transfer-branch" wire:model.live="branchId" class="pos-select">
                        <option value="">{{ __('Select a branch') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">
                                {{ __('Branch :number — :name', [
                                    'number' => $branch->branch_number,
                                    'name' => $branch->name,
                                ]) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="transfer-search" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem;">
                        {{ __('Search products') }}
                    </label>
                    <input
                        id="transfer-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        autocomplete="off"
                        placeholder="{{ __('Name or SKU…') }}"
                        class="pos-input"
                    />
                </div>
            </div>
        </div>

        <div class="pos-panel" style="padding: 0; overflow: hidden;">
            <div class="pos-cart-header" style="padding: 0.75rem 1rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem;">
                <div>
                    <span style="font-weight: 700; font-size: 0.9375rem;">{{ __('Warehouse products') }}</span>
                    <span class="pos-muted" style="display: block; font-size: 0.6875rem; font-weight: 500; margin-top: 0.125rem;">
                        {{ trans_choice(':count product with stock|:count products with stock', $products->count(), ['count' => $products->count()]) }}
                        · {{ trans_choice(':count selected|:count selected', $selectedCount, ['count' => $selectedCount]) }}
                    </span>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <button
                        type="button"
                        wire:click="clearQuantities"
                        class="pos-btn-secondary"
                        style="width: auto; padding: 0.5rem 0.875rem; font-size: 0.8125rem;"
                    >
                        {{ __('Clear quantities') }}
                    </button>
                    <button
                        type="button"
                        wire:click="transfer"
                        wire:confirm="{{ __('Transfer the entered quantities to the selected branch?') }}"
                        class="pos-btn-primary"
                        style="width: auto;"
                    >
                        {{ __('Transfer stock') }}
                    </button>
                </div>
            </div>

            @if ($products->isEmpty())
                <p class="pos-muted" style="margin: 0; padding: 2.5rem 1rem; text-align: center; font-size: 0.875rem;">
                    {{ trim($search) === ''
                        ? __('No products with warehouse stock.')
                        : __('No products match your search.') }}
                </p>
            @else
                <div style="overflow-x: auto;">
                    <table class="pos-table" style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                        <thead>
                            <tr style="text-align: left;">
                                <th style="padding: 0.5rem 0.75rem;">{{ __('Product') }}</th>
                                <th style="padding: 0.5rem 0.75rem;">{{ __('SKU') }}</th>
                                <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('In warehouse') }}</th>
                                <th style="padding: 0.5rem 0.75rem; width: 8rem;">{{ __('Qty to transfer') }}</th>
                                <th style="padding: 0.5rem 0.75rem; width: 11rem;">{{ __('Expiration date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                @php
                                    $qty = (int) ($quantities[$product->id] ?? 0);
                                @endphp
                                <tr @style(['background: rgba(2, 132, 199, 0.08)' => $qty > 0])>
                                    <td style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ $product->name }}</td>
                                    <td class="pos-muted" style="padding: 0.5rem 0.75rem;">{{ $product->sku ?? '—' }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end;">{{ number_format($product->quantity) }}</td>
                                    <td style="padding: 0.5rem 0.75rem;">
                                        <input
                                            type="number"
                                            min="0"
                                            max="{{ $product->quantity }}"
                                            wire:model.live="quantities.{{ $product->id }}"
                                            placeholder="0"
                                            class="pos-input"
                                            style="width: 6.5rem; padding: 0.375rem 0.5rem;"
                                        />
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem;">
                                        <input
                                            type="date"
                                            wire:model="expirations.{{ $product->id }}"
                                            class="pos-input"
                                            style="padding: 0.375rem 0.5rem;"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
