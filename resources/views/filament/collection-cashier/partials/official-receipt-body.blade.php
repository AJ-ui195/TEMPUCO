@php
    $coop = $coop ?? 'DIGOS CITY NATIONAL HIGH SCHOOL TEACHERS AND EMPLOYEES MULTI-PURPOSE COOPERATIVE (DICNHS TEMPUCO)';
    $address = $address ?? 'Rizal Ave., Zone 2 (Pob.) Digos City, Davao del Sur';
    $vatTin = $vatTin ?? '001-946-758-00000';
    $orNo = $orNo ?? '';
    $receivedFrom = $receivedFrom ?? '';
    $tin = $tin ?? '';
    $paymentFor = $paymentFor ?? '';
    $amount = (float) ($amount ?? 0);
    $date = $date ?? '';
    $cash = $cash ?? null;
@endphp

<div class="tmpc-pad">
    <div class="tmpc-pad__head">
        <div class="tmpc-pad__coop">{{ $coop }}</div>
        <div>{{ $address }}</div>
        <div>{{ __('VAT Reg. TIN') }} {{ $vatTin }}</div>
        <div class="tmpc-pad__title">{{ __('OFFICIAL RECEIPT') }}</div>
        <div class="tmpc-pad__no">{{ __('No.') }} {{ $orNo !== '' ? $orNo : '—' }}</div>
    </div>

    <div class="tmpc-pad__checks">
        <span>[{{ $cash === true ? 'X' : ' ' }}] {{ __('CASH') }}</span>
        <span>[{{ $cash === false ? 'X' : ' ' }}] {{ __('CHECKS') }}</span>
        <span>{{ __('Date') }}: {{ $date !== '' ? $date : '—' }}</span>
    </div>

    <div class="tmpc-pad__row"><strong>{{ __('RECEIVED FROM') }}:</strong> {{ $receivedFrom !== '' ? $receivedFrom : '—' }}</div>
    <div class="tmpc-pad__row"><strong>{{ __('TIN') }}:</strong> {{ $tin !== '' ? $tin : '—' }}</div>
    <div class="tmpc-pad__row"><strong>{{ __('Payment for') }}:</strong> {{ $paymentFor !== '' ? $paymentFor : '—' }}</div>

    <table class="tmpc-pad__table">
        <tr>
            <td>{{ __('Amount') }}</td>
            <td class="tmpc-pad__amt">{{ number_format($amount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>{{ __('TOTAL PAID AMOUNT') }}</strong></td>
            <td class="tmpc-pad__amt"><strong>{{ number_format($amount, 2) }}</strong></td>
        </tr>
    </table>

    <div class="tmpc-pad__sign">{{ __('By') }}: ______________________</div>
    <div class="tmpc-pad__note">{{ __('THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX') }}</div>
</div>
