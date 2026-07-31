@php
    /** @var \App\Models\PosSale $sale */
    /** @var \App\Models\User|null $cashier */
    $isCredit = $sale->isUnsettled();
    $channelLabel = $sale->sale_channel?->getLabel() ?? __('Grocery');
@endphp

<div class="pos-receipt-paper">
    <div class="pos-receipt-paper__center">
        <div class="pos-receipt-paper__brand">{{ __('TEMPUCO') }}</div>
        <div>{{ $channelLabel }} {{ __('POS') }}</div>
        <div class="pos-receipt-paper__subtitle">{{ __('Sales receipt') }}</div>
    </div>

    <div class="pos-receipt-paper__divider"></div>

    <div>
        <div>{{ __('Ref') }}: {{ $sale->reference }}</div>
        <div>{{ \App\Support\PhilippineTime::format($sale->created_at) }}</div>
        @if ($cashier)
            <div>{{ __('Cashier') }}: {{ $cashier->name }}</div>
        @endif
        @if ($sale->member)
            <div>{{ __('Member') }}: {{ $sale->member->name }}</div>
        @endif
        <div>{{ __('Payment') }}: {{ $isCredit ? __('Credit') : __('Cash') }}</div>
    </div>

    <div class="pos-receipt-paper__divider"></div>

    <table class="pos-receipt-paper__table">
        <thead>
            <tr>
                <th>{{ __('Item') }}</th>
                <th class="pos-receipt-paper__qty">{{ __('Qty') }}</th>
                <th class="pos-receipt-paper__amount">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $line)
                <tr>
                    <td>
                        {{ $line->productName() }}
                        <div class="pos-receipt-paper__unit-price">@ ₱{{ number_format((float) $line->unit_price, 2) }}</div>
                    </td>
                    <td class="pos-receipt-paper__qty">{{ $line->quantity }}</td>
                    <td class="pos-receipt-paper__amount">₱{{ number_format((float) $line->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pos-receipt-paper__divider"></div>

    <table class="pos-receipt-paper__table pos-receipt-paper__totals">
        <tr>
            <td class="pos-receipt-paper__label" colspan="2">{{ __('Subtotal') }}</td>
            <td class="pos-receipt-paper__amount">₱{{ number_format((float) $sale->total, 2) }}</td>
        </tr>
        <tr>
            <td class="pos-receipt-paper__label" colspan="2">{{ __('Amount paid') }}</td>
            <td class="pos-receipt-paper__amount">₱{{ number_format((float) $sale->amount_paid, 2) }}</td>
        </tr>
        @if ($isCredit)
            <tr>
                <td class="pos-receipt-paper__label" colspan="2">{{ __('Charged to account') }}</td>
                <td class="pos-receipt-paper__amount">₱{{ number_format($sale->outstandingAmount(), 2) }}</td>
            </tr>
        @elseif ((float) $sale->change_amount > 0)
            <tr>
                <td class="pos-receipt-paper__label" colspan="2">{{ __('Change') }}</td>
                <td class="pos-receipt-paper__amount">₱{{ number_format((float) $sale->change_amount, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td class="pos-receipt-paper__label pos-receipt-paper__grand" colspan="2">{{ __('Total') }}</td>
            <td class="pos-receipt-paper__amount pos-receipt-paper__grand">₱{{ number_format((float) $sale->total, 2) }}</td>
        </tr>
    </table>

    <div class="pos-receipt-paper__divider"></div>

    <div class="pos-receipt-paper__center pos-receipt-paper__footer">
        {{ __('Thank you!') }}
    </div>
</div>
