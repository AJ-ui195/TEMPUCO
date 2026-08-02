<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $sale = $this->getSelectedSale();
        $channelLabel = $this->getSaleChannel()->getLabel();
    @endphp

    <div class="fi-pos-ui">
        <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
            <h2 style="margin: 0; font-size: 1.125rem; font-weight: 700;">{{ __('Void a completed sale') }}</h2>
            <p class="pos-muted" style="margin: 0.375rem 0 1rem; font-size: 0.8125rem;">
                {{ __('Voiding returns the items to :channel stock, lowers the sale total, and reduces any credit charged to the member. The sale stays on record with the reason you give.', ['channel' => strtolower($channelLabel)]) }}
            </p>

            @if ($sale)
                <div style="display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.75rem;">
                    <div>
                        <div style="font-weight: 700; font-size: 1rem;">{{ $sale->reference }}</div>
                        <div class="pos-muted" style="font-size: 0.8125rem; margin-top: 0.125rem;">
                            {{ \App\Support\PhilippineTime::format($sale->created_at) }}
                            · {{ $sale->paymentTypeLabel() }}
                            @if ($sale->memberName())
                                · {{ __('Member') }}: {{ $sale->memberName() }}
                            @endif
                            @if ($sale->cashierName())
                                · {{ __('Cashier') }}: {{ $sale->cashierName() }}
                            @endif
                        </div>
                    </div>
                    <button type="button" wire:click="clearSale" class="pos-btn-secondary" style="width: auto; padding: 0.5rem 0.875rem; font-size: 0.8125rem;">
                        {{ __('Choose another sale') }}
                    </button>
                </div>
            @else
                <label for="void-sale-search" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem;">
                    {{ __('Search sale') }}
                </label>
                <input
                    id="void-sale-search"
                    type="search"
                    wire:model.live.debounce.300ms="saleSearch"
                    autocomplete="off"
                    placeholder="{{ __('Receipt reference or member name…') }}"
                    class="pos-input"
                />
            @endif
        </div>

        @if (! $sale)
            @php($recentSales = $this->getRecentSales())

            <div class="pos-panel" style="padding: 0; overflow: hidden;">
                <div class="pos-cart-header" style="padding: 0.75rem 1rem;">
                    <span style="font-weight: 700; font-size: 0.9375rem;">
                        {{ trim($saleSearch) === '' ? __('Latest :channel sales', ['channel' => strtolower($channelLabel)]) : __('Search results') }}
                    </span>
                </div>

                @if ($recentSales->isEmpty())
                    <p class="pos-muted" style="margin: 0; padding: 2.5rem 1rem; text-align: center; font-size: 0.875rem;">
                        {{ __('No sales found.') }}
                    </p>
                @else
                    <div style="overflow-x: auto;">
                        <table class="pos-table" style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                            <thead>
                                <tr style="text-align: left;">
                                    <th style="padding: 0.5rem 0.75rem;">{{ __('Reference') }}</th>
                                    <th style="padding: 0.5rem 0.75rem;">{{ __('Date & time') }}</th>
                                    <th style="padding: 0.5rem 0.75rem;">{{ __('Member') }}</th>
                                    <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Items') }}</th>
                                    <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Total') }}</th>
                                    <th style="padding: 0.5rem 0.75rem;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentSales as $row)
                                    <tr>
                                        <td style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ $row->reference }}</td>
                                        <td class="pos-muted" style="padding: 0.5rem 0.75rem; white-space: nowrap;">
                                            {{ \App\Support\PhilippineTime::format($row->created_at) }}
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem;">{{ $row->memberName() ?? '—' }}</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: end;">{{ number_format($row->items_count) }}</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 600;">₱{{ number_format((float) $row->total, 2) }}</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: end;">
                                            <button
                                                type="button"
                                                wire:click="selectSale({{ $row->id }})"
                                                class="pos-btn-secondary"
                                                style="width: auto; padding: 0.375rem 0.75rem; font-size: 0.75rem;"
                                            >
                                                {{ __('Open') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @else
            @php($activeCount = $sale->items->filter(fn ($item) => $item->voidRecord === null)->count())

            <div class="pos-panel" style="padding: 0; overflow: hidden; margin-bottom: 1rem;">
                <div class="pos-cart-header" style="padding: 0.75rem 1rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem;">
                    <span style="font-weight: 700; font-size: 0.9375rem;">{{ __('Items on this sale') }}</span>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
                        <button type="button" wire:click="selectAllItems" class="pos-period-btn">{{ __('Select all') }}</button>
                        <button type="button" wire:click="clearItemSelection" class="pos-period-btn">{{ __('Clear selection') }}</button>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="pos-table" style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                        <thead>
                            <tr style="text-align: left;">
                                <th style="padding: 0.5rem 0.75rem; width: 2.5rem;"></th>
                                <th style="padding: 0.5rem 0.75rem;">{{ __('Product') }}</th>
                                <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Qty') }}</th>
                                <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Unit price') }}</th>
                                <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Amount') }}</th>
                                <th style="padding: 0.5rem 0.75rem;">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sale->items as $item)
                                @php($isVoided = $item->voidRecord !== null)
                                <tr @style([
                                    'background: rgba(220, 38, 38, 0.08)' => ! $isVoided && $this->isItemSelected($item->id),
                                    'opacity: 0.55' => $isVoided,
                                ])>
                                    <td style="padding: 0.5rem 0.75rem;">
                                        @unless ($isVoided)
                                            <input
                                                type="checkbox"
                                                wire:click="toggleItem({{ $item->id }})"
                                                @checked($this->isItemSelected($item->id))
                                                aria-label="{{ __('Void :product', ['product' => $item->productName()]) }}"
                                                style="width: 1rem; height: 1rem; accent-color: #dc2626;"
                                            />
                                        @endunless
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem;">
                                        <span style="font-weight: 600; {{ $isVoided ? 'text-decoration: line-through;' : '' }}">{{ $item->productName() }}</span>
                                        <span class="pos-muted" style="display: block; font-size: 0.6875rem;">{{ $item->productSku() ?? '—' }}</span>
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end;">{{ number_format($item->quantity) }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end;">₱{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 600;">₱{{ number_format((float) $item->line_total, 2) }}</td>
                                    <td style="padding: 0.5rem 0.75rem;">
                                        @if ($isVoided)
                                            <span style="font-weight: 700; color: rgb(185 28 28);">{{ __('Voided') }}</span>
                                            <span class="pos-muted" style="display: block; font-size: 0.6875rem;">
                                                {{ $item->voidRecord->reason }}
                                                @if ($item->voidRecord->cashier)
                                                    · {{ $item->voidRecord->cashier->name }}
                                                @endif
                                                · {{ \App\Support\PhilippineTime::format($item->voidRecord->created_at) }}
                                            </span>
                                        @else
                                            <span class="pos-muted">{{ __('Active') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 2px solid rgb(203 213 225); font-weight: 700;">
                                <td colspan="4" style="padding: 0.75rem; text-align: end;">{{ __('Sale total now') }}</td>
                                <td style="padding: 0.75rem; text-align: end;">₱{{ number_format((float) $sale->total, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="pos-panel" style="padding: 1rem 1.25rem;">
                @if ($activeCount === 0)
                    <p style="margin: 0; font-size: 0.875rem; font-weight: 600; color: rgb(185 28 28);">
                        {{ __('Every item on this sale has been voided.') }}
                    </p>
                @else
                    <label for="void-reason" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.375rem;">
                        {{ __('Reason for void') }}
                    </label>
                    <input
                        id="void-reason"
                        type="text"
                        wire:model="reason"
                        maxlength="255"
                        placeholder="{{ __('e.g. wrong item scanned, customer changed mind…') }}"
                        class="pos-input"
                    />

                    @if ($voidError)
                        <p style="margin: 0.75rem 0 0; font-size: 0.8125rem; font-weight: 600; color: rgb(185 28 28);">
                            {{ $voidError }}
                        </p>
                    @endif

                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem;">
                        <button
                            type="button"
                            wire:click="voidSelected"
                            wire:confirm="{{ __('Void the selected items and return them to stock?') }}"
                            class="pos-btn-primary"
                            style="width: auto; padding: 0.625rem 1rem;"
                        >
                            {{ __('Void selected items') }}
                        </button>
                        <button
                            type="button"
                            wire:click="voidWholeSale"
                            wire:confirm="{{ __('Void this entire sale and return every item to stock?') }}"
                            class="pos-btn-danger"
                            style="width: auto;"
                        >
                            {{ __('Void entire sale') }}
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
