@php
    $coop = $coop ?? 'DIGOS CITY NATIONAL HIGH SCHOOL TEACHERS AND EMPLOYEES MULTI-PURPOSE COOPERATIVE (DICNHS TEMPUCO)';
    $address = $address ?? 'Rizal Ave., Zone 2 (Pob.) Digos City, Davao del Sur';
    $vatTin = $vatTin ?? '001-946-758-00000';
    $invoiceNo = $invoiceNo ?? '';
    $soldTo = $soldTo ?? '';
    $tin = $tin ?? '';
    $item = $item ?? '';
    $amount = (float) ($amount ?? 0);
    $date = $date ?? '';
    $cash = $cash ?? null;
@endphp

<div class="tmpc-pad">
    <div class="tmpc-pad__head">
        <div class="tmpc-pad__coop">{{ $coop }}</div>
        <div>{{ $address }}</div>
        <div>{{ __('VAT Reg. TIN') }} {{ $vatTin }}</div>
        <div class="tmpc-pad__title">{{ __('INVOICE') }}</div>
        <div class="tmpc-pad__no">{{ __('No.') }} {{ $invoiceNo !== '' ? $invoiceNo : '—' }}</div>
    </div>

    <div class="tmpc-pad__checks">
        <span>[{{ $cash === true ? 'X' : ' ' }}] {{ __('CASH') }}</span>
        <span>[{{ $cash === false ? 'X' : ' ' }}] {{ __('CHECKS') }}</span>
        <span>{{ __('Date') }}: {{ $date !== '' ? $date : '—' }}</span>
    </div>

    <div class="tmpc-pad__row"><strong>{{ __('SOLD TO') }}:</strong> {{ $soldTo !== '' ? $soldTo : '—' }}</div>
    <div class="tmpc-pad__row"><strong>{{ __('TIN') }}:</strong> {{ $tin !== '' ? $tin : '—' }}</div>

    <table class="tmpc-pad__table">
        <tr>
            <th>{{ __('Item / Nature of service') }}</th>
            <th class="tmpc-pad__amt">{{ __('Amount') }}</th>
        </tr>
        <tr>
            <td>{{ $item !== '' ? $item : '—' }}</td>
            <td class="tmpc-pad__amt">{{ number_format($amount, 2) }}</td>
        </tr>
        <tr>
            <td>{{ __('Total Sales') }}</td>
            <td class="tmpc-pad__amt">{{ number_format($amount, 2) }}</td>
        </tr>
        <tr>
            <td>{{ __('VAT') }}</td>
            <td class="tmpc-pad__amt">0.00</td>
        </tr>
        <tr>
            <td><strong>{{ __('TOTAL AMOUNT DUE') }}</strong></td>
            <td class="tmpc-pad__amt"><strong>{{ number_format($amount, 2) }}</strong></td>
        </tr>
    </table>

    <div class="tmpc-pad__sign">{{ __('Auth. Signature') }}: ______________________</div>
</div>
