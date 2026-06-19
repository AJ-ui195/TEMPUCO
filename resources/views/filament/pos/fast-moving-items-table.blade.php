@php
    /** @var \Illuminate\Support\Collection<int, array{rank: int, name: string, sku: ?string, quantity_sold: int, revenue: float}> $items */
@endphp

@if ($items->isEmpty())
    <p style="margin: 0; font-size: 0.875rem; color: rgb(100 116 139);">
        {{ __('No sales recorded in this period.') }}
    </p>
@else
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
            <thead>
                <tr style="border-bottom: 1px solid rgb(226 232 240); text-align: left;">
                    <th style="padding: 0.5rem 0.75rem 0.5rem 0; font-weight: 600; width: 3rem;">{{ __('Rank') }}</th>
                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ __('Product') }}</th>
                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ __('SKU') }}</th>
                    <th style="padding: 0.5rem 0.75rem; font-weight: 600; text-align: right;">{{ __('Qty sold') }}</th>
                    <th style="padding: 0.5rem 0 0.5rem 0.75rem; font-weight: 600; text-align: right;">{{ __('Revenue') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr style="border-bottom: 1px solid rgb(241 245 249);">
                        <td style="padding: 0.625rem 0.75rem 0.625rem 0; font-weight: 700; color: rgb(2 132 199);">
                            #{{ $item['rank'] }}
                        </td>
                        <td style="padding: 0.625rem 0.75rem; font-weight: 600;">{{ $item['name'] }}</td>
                        <td style="padding: 0.625rem 0.75rem; color: rgb(100 116 139);">{{ $item['sku'] ?? '—' }}</td>
                        <td style="padding: 0.625rem 0.75rem; text-align: right; font-weight: 600;">{{ number_format($item['quantity_sold']) }}</td>
                        <td style="padding: 0.625rem 0 0.625rem 0.75rem; text-align: right;">₱{{ number_format($item['revenue'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
