@php
    /** @var \App\Models\PosCreditPayment $payment */
    /** @var \App\Models\User|null $cashier */
    $channelLabel = $payment->sale_channel?->getLabel() ?? __('Grocery');
@endphp

<div class="pos-receipt-paper">
    <div class="pos-receipt-paper__center">
        <div class="pos-receipt-paper__brand">{{ __('TEMPUCO') }}</div>
        <div>{{ $channelLabel }} {{ __('POS') }}</div>
        <div class="pos-receipt-paper__subtitle">{{ __('Credit payment receipt') }}</div>
    </div>

    <div class="pos-receipt-paper__divider"></div>

    <div>
        <div>{{ __('Ref') }}: {{ $payment->reference }}</div>
        <div>{{ \App\Support\PhilippineTime::format($payment->created_at) }}</div>
        @if ($cashier)
            <div>{{ __('Cashier') }}: {{ $cashier->name }}</div>
        @endif
        @if ($payment->member)
            <div>{{ __('Member') }}: {{ $payment->member->name }}</div>
        @endif
        <div>{{ __('Payment') }}: {{ __('Credit settlement') }}</div>
    </div>

    <div class="pos-receipt-paper__divider"></div>

    <table class="pos-receipt-paper__table pos-receipt-paper__totals">
        <tr>
            <td class="pos-receipt-paper__label" colspan="2">{{ __('Previous balance') }}</td>
            <td class="pos-receipt-paper__amount">₱{{ number_format($balanceBefore, 2) }}</td>
        </tr>
        <tr>
            <td class="pos-receipt-paper__label" colspan="2">{{ __('Payment received') }}</td>
            <td class="pos-receipt-paper__amount">₱{{ number_format((float) $payment->amount, 2) }}</td>
        </tr>
        <tr>
            <td class="pos-receipt-paper__label pos-receipt-paper__grand" colspan="2">{{ __('Remaining balance') }}</td>
            <td class="pos-receipt-paper__amount pos-receipt-paper__grand">₱{{ number_format($balanceAfter, 2) }}</td>
        </tr>
    </table>

    <div class="pos-receipt-paper__divider"></div>

    <div class="pos-receipt-paper__center pos-receipt-paper__footer">
        @if ($balanceAfter > 0)
            {{ __('Partial payment — balance carried forward.') }}
        @else
            {{ __(':channel credit fully settled. Thank you!', ['channel' => $channelLabel]) }}
        @endif
    </div>
</div>
