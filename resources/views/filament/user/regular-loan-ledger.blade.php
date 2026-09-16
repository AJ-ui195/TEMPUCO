<div class="ll-page">
    @include('filament.user.regular-loan-schedule', [
        'schedule' => $schedule,
        'borrowerName' => $borrowerName,
        'blankAmounts' => $blankAmounts ?? false,
        'loan' => $loan ?? null,
        'paidCash' => $paidCash ?? 0,
    ])
</div>

@include('filament.user.partials.loan-ledger-styles')
