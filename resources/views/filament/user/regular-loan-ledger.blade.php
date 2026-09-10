<div class="ll-page">
    @include('filament.user.regular-loan-schedule', [
        'schedule' => $schedule,
        'borrowerName' => $borrowerName,
        'blankAmounts' => $blankAmounts ?? false,
    ])
</div>

@include('filament.user.partials.loan-ledger-styles')
