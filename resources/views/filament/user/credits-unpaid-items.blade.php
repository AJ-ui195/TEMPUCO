@php
    /** @var \Illuminate\Support\Collection<int, array{date: string, name: string, quantity: int, unit_price: float, line_total: float, reference: string}> $items */
@endphp

@if ($items->isEmpty())
    <div class="mp-empty">
        {{ __('You have no unpaid :channel items.', ['channel' => strtolower($channelLabel)]) }}
    </div>
@else
    <div class="mp-table-wrap">
        <table class="mp-table">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Item') }}</th>
                    <th style="text-align: right;">{{ __('Qty') }}</th>
                    <th style="text-align: right;">{{ __('Unit price') }}</th>
                    <th style="text-align: right;">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td style="white-space: nowrap; color: #64748b;">
                            {{ $item['date'] }}
                        </td>
                        <td>
                            <span style="font-weight: 600;">{{ $item['name'] }}</span>
                            <span style="display: block; font-size: 0.75rem; color: #94a3b8;">{{ $item['reference'] }}</span>
                        </td>
                        <td style="text-align: right;">{{ $item['quantity'] }}</td>
                        <td style="text-align: right;">₱{{ number_format($item['unit_price'], 2) }}</td>
                        <td style="text-align: right; font-weight: 600;">₱{{ number_format($item['line_total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right;">{{ __('Subtotal') }}</td>
                    <td style="text-align: right;">
                        ₱{{ number_format($items->sum('line_total'), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
