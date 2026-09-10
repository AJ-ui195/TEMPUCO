@php
    $ledgerSubtitle = $ledgerSubtitle ?? __('Loan account');
@endphp

<header class="ll-sheet-head">
    <div class="ll-head-coop">
        {{ __('Digos City National High School Teacher & Employee Multi-Purpose Cooperative (DICNHS-TEMPUCO)') }}
    </div>
    <div class="ll-head-school">{{ __('Digos City National High School') }}</div>
    <div class="ll-head-city">{{ __('Digos City') }}</div>
    <div class="ll-head-title">{{ __('Individual ledger') }}</div>
    <div class="ll-head-subtitle">{{ $ledgerSubtitle }}</div>
</header>
