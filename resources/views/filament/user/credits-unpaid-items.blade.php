@php
    /** @var \Illuminate\Support\Collection<int, array{date: string, name: string, quantity: int, unit_price: float, line_total: float, reference: string}> $items */
@endphp

@if ($items->isEmpty())
    <p style="margin: 0; font-size: 0.875rem; color: rgb(100 116 139);">
        {{ __('You have no unpaid :channel items.', ['channel' => strtolower($channelLabel)]) }}
    </p>
@else
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
            <thead>
                <tr style="border-bottom: 1px solid rgb(226 232 240); text-align: left;">
                    <th style="padding: 0.5rem 0.75rem 0.5rem 0; font-weight: 600;">{{ __('Date') }}</th>
                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ __('Item') }}</th>
                    <th style="padding: 0.5rem 0.75rem; font-weight: 600; text-align: right;">{{ __('Qty') }}</th>
                    <th style="padding: 0.5rem 0.75rem; font-weight: 600; text-align: right;">{{ __('Unit price') }}</th>
                    <th style="padding: 0.5rem 0 0.5rem 0.75rem; font-weight: 600; text-align: right;">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr style="border-bottom: 1px solid rgb(241 245 249);">
                        <td style="padding: 0.625rem 0.75rem 0.625rem 0; color: rgb(100 116 139); white-space: nowrap;">
                            {{ $item['date'] }}
                        </td>
                        <td style="padding: 0.625rem 0.75rem;">
                            <span style="font-weight: 600;">{{ $item['name'] }}</span>
                            <span style="display: block; font-size: 0.75rem; color: rgb(148 163 184);">{{ $item['reference'] }}</span>
                        </td>
                        <td style="padding: 0.625rem 0.75rem; text-align: right;">{{ $item['quantity'] }}</td>
                        <td style="padding: 0.625rem 0.75rem; text-align: right;">₱{{ number_format($item['unit_price'], 2) }}</td>
                        <td style="padding: 0.625rem 0 0.625rem 0.75rem; text-align: right; font-weight: 600;">₱{{ number_format($item['line_total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="padding: 0.75rem 0.75rem 0 0; font-weight: 700; text-align: right;">{{ __('Subtotal') }}</td>
                    <td style="padding: 0.75rem 0 0 0.75rem; font-weight: 700; text-align: right;">
                        ₱{{ number_format($items->sum('line_total'), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
