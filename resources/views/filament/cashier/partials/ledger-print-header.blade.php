<div class="doc-head">
    <div class="doc-logo">
        <img src="{{ asset('images/DICNHSLOGO1.png') }}" alt="{{ __('TEMPUCO logo') }}">
    </div>

    <div class="doc-head-text">
        <div class="doc-brand">{{ __('TEMPUCO') }}</div>
        <div class="doc-org">{{ __('Digos City National High School Teachers and Employees Multi-Purpose Cooperative') }}</div>
        <div class="doc-title">{{ $title }}</div>
        <div class="doc-meta">
            {{ __('Period: :range', ['range' => $rangeLabel]) }}
            · {{ __('Channel: :channel', ['channel' => $channelLabel]) }}
            · {{ __('Printed :datetime', ['datetime' => $printedAt]) }}
        </div>
    </div>

    <div class="doc-head-spacer" aria-hidden="true"></div>
</div>
