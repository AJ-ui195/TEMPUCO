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
    </div>
</x-filament-panels::page>
