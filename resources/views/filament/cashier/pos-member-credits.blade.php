<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $members = $this->getMembersWithCredit();
        $totalOutstanding = $this->getTotalOutstanding();
    @endphp

    <div class="fi-pos-ui">
        <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
            <h2 style="margin: 0; font-size: 1.125rem; font-weight: 700;">{{ __('Member credits') }}</h2>
            <p class="pos-muted" style="margin: 0.375rem 0 1rem; font-size: 0.8125rem;">
                {{ __('Members with unpaid grocery or canteen purchases charged to their account.') }}
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('Members with credit') }}</div>
                    <div class="pos-stat-value">{{ number_format($members->count()) }}</div>
                </div>
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('Total outstanding') }}</div>
                    <div class="pos-stat-value" style="color: rgb(4 120 87);">₱{{ number_format($totalOutstanding, 2) }}</div>
                </div>
            </div>

            <label for="member-credit-search" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem;">
                {{ __('Search member') }}
            </label>
            <input
                id="member-credit-search"
                type="search"
                wire:model.live.debounce.300ms="memberSearch"
                autocomplete="off"
                placeholder="{{ __('Name, email, or phone…') }}"
                class="pos-input"
            />
        </div>

        @if ($members->isEmpty())
            <div class="pos-panel" style="padding: 2.5rem 1.5rem; text-align: center;">
                <p class="pos-muted" style="margin: 0; font-size: 0.9375rem;">
                    @if (trim($memberSearch) !== '')
                        {{ __('No members with credit match your search.') }}
                    @else
                        {{ __('No members currently have outstanding credit.') }}
                    @endif
                </p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach ($members as $row)
                    @php
                        $member = $row['member'];
                        $items = $row['items'];
                    @endphp

                    <div class="pos-panel" style="padding: 0; overflow: hidden;">
                        <div class="pos-cart-header" style="padding: 0.875rem 1rem; display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.75rem;">
                            <div>
                                <div style="font-weight: 700; font-size: 1rem;">{{ $member->name }}</div>
                                <div class="pos-muted" style="font-size: 0.8125rem; margin-top: 0.125rem;">
                                    {{ $member->email }}
                                    @if ($member->cellphone)
                                        · {{ $member->cellphone }}
                                    @endif
                                </div>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; font-size: 0.8125rem;">
                                @if ($row['grocery_outstanding'] > 0)
                                    <div style="text-align: right;">
                                        <span class="pos-muted" style="display: block; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase;">{{ __('Grocery') }}</span>
                                        <span style="font-weight: 700;">₱{{ number_format($row['grocery_outstanding'], 2) }}</span>
                                    </div>
                                @endif
                                @if ($row['canteen_outstanding'] > 0)
                                    <div style="text-align: right;">
                                        <span class="pos-muted" style="display: block; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase;">{{ __('Canteen') }}</span>
                                        <span style="font-weight: 700;">₱{{ number_format($row['canteen_outstanding'], 2) }}</span>
                                    </div>
                                @endif
                                <div style="text-align: right; padding-left: 0.75rem; border-left: 1px solid rgb(226 232 240);">
                                    <span class="pos-muted" style="display: block; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase;">{{ __('Total') }}</span>
                                    <span style="font-weight: 700; color: rgb(4 120 87);">₱{{ number_format($row['total_outstanding'], 2) }}</span>
                                </div>
                            </div>
                        </div>

                        @php($recentPayments = $this->getRecentPayments($member->id))

                        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.75rem 1rem; border-bottom: 1px solid rgb(226 232 240);">
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                @if ($row['grocery_outstanding'] > 0)
                                    <button
                                        type="button"
                                        wire:click="openPaymentModal({{ $member->id }}, '{{ \App\Enums\PosSaleChannel::Grocery->value }}')"
                                        class="pos-btn-primary"
                                        style="width: auto; padding: 0.5rem 0.875rem; font-size: 0.8125rem;"
                                    >
                                        {{ __('Pay grocery credit') }}
                                    </button>
                                @endif
                                @if ($row['canteen_outstanding'] > 0)
                                    <button
                                        type="button"
                                        wire:click="openPaymentModal({{ $member->id }}, '{{ \App\Enums\PosSaleChannel::Canteen->value }}')"
                                        class="pos-btn-primary"
                                        style="width: auto; padding: 0.5rem 0.875rem; font-size: 0.8125rem;"
                                    >
                                        {{ __('Pay canteen credit') }}
                                    </button>
                                @endif
                                <a
                                    href="{{ \App\Filament\Cashier\Pages\PosMemberLedgerPage::getUrl(panel: 'pos').'?member='.$member->id }}"
                                    class="pos-btn-secondary"
                                    style="width: auto; padding: 0.5rem 0.875rem; font-size: 0.8125rem; display: inline-flex; align-items: center; text-decoration: none;"
                                >
                                    {{ __('View ledger') }}
                                </a>
                            </div>

                            @if ($recentPayments->isNotEmpty())
                                <div class="pos-muted" style="font-size: 0.75rem; text-align: right;">
                                    <span style="font-weight: 600;">{{ __('Recent payments') }}:</span>
                                    @foreach ($recentPayments as $payment)
                                        <span style="display: block;">
                                            ₱{{ number_format((float) $payment->amount, 2) }}
                                            · {{ $payment->sale_channel?->getLabel() }}
                                            · {{ \App\Support\PhilippineTime::format($payment->created_at) }}
                                            @if ($payment->cashier)
                                                · {{ $payment->cashier->name }}
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($items->isEmpty())
                            <p class="pos-muted" style="margin: 0; padding: 1.25rem 1rem; font-size: 0.875rem;">
                                {{ __('No unpaid items on record.') }}
                            </p>
                        @else
                            <div style="overflow-x: auto;">
                                <table class="pos-table" style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                                    <thead>
                                        <tr style="text-align: left;">
                                            <th style="padding: 0.5rem 0.75rem;">{{ __('Date') }}</th>
                                            <th style="padding: 0.5rem 0.75rem;">{{ __('Channel') }}</th>
                                            <th style="padding: 0.5rem 0.75rem;">{{ __('Item') }}</th>
                                            <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Qty') }}</th>
                                            <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Unit price') }}</th>
                                            <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            <tr>
                                                <td class="pos-muted" style="padding: 0.5rem 0.75rem; white-space: nowrap;">{{ $item['date'] }}</td>
                                                <td style="padding: 0.5rem 0.75rem;">{{ $item['channel'] }}</td>
                                                <td style="padding: 0.5rem 0.75rem;">
                                                    <span style="font-weight: 600;">{{ $item['name'] }}</span>
                                                    <span class="pos-muted" style="display: block; font-size: 0.6875rem;">{{ $item['reference'] }}</span>
                                                </td>
                                                <td style="padding: 0.5rem 0.75rem; text-align: end;">{{ $item['quantity'] }}</td>
                                                <td style="padding: 0.5rem 0.75rem; text-align: end;">₱{{ number_format($item['unit_price'], 2) }}</td>
                                                <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 600;">₱{{ number_format($item['line_total'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="font-weight: 700;">
                                            <td colspan="5" style="padding: 0.75rem; text-align: end;">{{ __('Subtotal') }}</td>
                                            <td style="padding: 0.75rem; text-align: end;">₱{{ number_format($items->sum('line_total'), 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @php($paymentMember = $this->getPaymentMember())

        @if ($paymentMember)
            @php($paymentChannelEnum = $this->getPaymentSaleChannel())
            @php($paymentOutstanding = $this->getPaymentOutstanding())

            <div class="pos-credit-modal" wire:key="credit-payment-{{ $paymentMember->id }}-{{ $paymentChannel }}">
                <div class="pos-credit-modal__backdrop" wire:click="closePaymentModal"></div>
                <div class="pos-credit-modal__dialog">
                    <h3 style="margin: 0 0 0.25rem; font-size: 1.0625rem; font-weight: 700;">
                        {{ __('Pay :channel credit', ['channel' => strtolower($paymentChannelEnum?->getLabel() ?? '')]) }}
                    </h3>
                    <p class="pos-muted" style="margin: 0 0 1rem; font-size: 0.8125rem;">
                        {{ $paymentMember->name }}
                    </p>

                    <div class="pos-panel pos-stat" style="margin-bottom: 1rem;">
                        <div class="pos-muted pos-stat-label">{{ __('Outstanding balance') }}</div>
                        <div class="pos-stat-value" style="color: rgb(4 120 87);">₱{{ number_format($paymentOutstanding, 2) }}</div>
                    </div>

                    <label for="credit-payment-amount" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.375rem;">
                        {{ __('Amount paid (₱)') }}
                    </label>
                    <input
                        id="credit-payment-amount"
                        type="number"
                        min="0"
                        step="0.01"
                        max="{{ number_format($paymentOutstanding, 2, '.', '') }}"
                        wire:model.live="paymentAmount"
                        wire:keydown.enter.prevent="recordPayment"
                        class="pos-input"
                        style="padding: 0.625rem 0.75rem; font-size: 1rem;"
                    />

                    <button
                        type="button"
                        wire:click="useFullPaymentAmount"
                        class="pos-btn-secondary"
                        style="width: auto; margin-top: 0.5rem; padding: 0.375rem 0.75rem; font-size: 0.75rem;"
                    >
                        {{ __('Pay full balance') }}
                    </button>

                    @if ($paymentError)
                        <p style="margin: 0.75rem 0 0; font-size: 0.8125rem; font-weight: 600; color: rgb(185 28 28);">
                            {{ $paymentError }}
                        </p>
                    @endif

                    @php($remainingPreview = $this->getPaymentRemainingPreview())

                    <div style="display: flex; justify-content: space-between; margin-top: 1rem; font-size: 0.875rem; font-weight: 600;">
                        <span>{{ __('Remaining after payment') }}</span>
                        <span style="color: {{ $remainingPreview > 0 ? 'rgb(4 120 87)' : 'rgb(100 116 139)' }};">
                            ₱{{ number_format($remainingPreview, 2) }}
                        </span>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1.25rem;">
                        <button type="button" wire:click="recordPayment" class="pos-btn-primary">
                            {{ __('Record payment') }}
                        </button>
                        <button type="button" wire:click="closePaymentModal" class="pos-btn-secondary">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        .fi-pos-ui .pos-credit-modal {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .fi-pos-ui .pos-credit-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
        }

        .fi-pos-ui .pos-credit-modal__dialog {
            position: relative;
            width: 100%;
            max-width: 22rem;
            padding: 1.25rem;
            border-radius: 0.75rem;
            background: #fff;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25);
        }

        .dark .fi-pos-ui .pos-credit-modal__dialog {
            background: rgb(30 41 59);
            color: #fff;
        }
    </style>
</x-filament-panels::page>
