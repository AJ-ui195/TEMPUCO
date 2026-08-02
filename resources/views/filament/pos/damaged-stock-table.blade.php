@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\PosInventoryDamage> $damages */
@endphp

@if ($damages->isEmpty())
    <p style="margin: 0; font-size: 0.875rem; color: rgb(100 116 139);">
        {{ __('No damaged stock has been pulled out yet.') }}
    </p>
@else
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
            <thead>
                <tr style="border-bottom: 1px solid rgb(226 232 240); text-align: left;">
                    <th style="padding: 0.5rem 0.75rem 0.5rem 0; font-weight: 600;">{{ __('Date & time') }}</th>
                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ __('Product') }}</th>
                    <th style="padding: 0.5rem 0.75rem; font-weight: 600; text-align: right;">{{ __('Qty') }}</th>
                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ __('Reason') }}</th>
                    <th style="padding: 0.5rem 0 0.5rem 0.75rem; font-weight: 600;">{{ __('Recorded by') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($damages as $damage)
                    <tr style="border-bottom: 1px solid rgb(241 245 249);">
                        <td style="padding: 0.625rem 0.75rem 0.625rem 0; color: rgb(100 116 139); white-space: nowrap;">
                            {{ \App\Support\PhilippineTime::format($damage->created_at) }}
                        </td>
                        <td style="padding: 0.625rem 0.75rem; font-weight: 600;">
                            {{ $damage->inventoryItem?->name ?? __('Unknown product') }}
                            <span style="display: block; font-size: 0.75rem; font-weight: 400; color: rgb(100 116 139);">
                                {{ $damage->inventoryItem?->sku ?? '—' }}
                            </span>
                        </td>
                        <td style="padding: 0.625rem 0.75rem; text-align: right; font-weight: 700; color: rgb(185 28 28);">
                            −{{ number_format($damage->quantity) }}
                        </td>
                        <td style="padding: 0.625rem 0.75rem;">{{ $damage->reason }}</td>
                        <td style="padding: 0.625rem 0 0.625rem 0.75rem; color: rgb(100 116 139);">
                            {{ $damage->recordedBy?->name ?? '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
