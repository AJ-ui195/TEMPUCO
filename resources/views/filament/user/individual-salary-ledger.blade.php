<div class="ll-page">
    @include('filament.user.salary-individual-ledger', [
        'user' => $user,
        'loan' => $loan ?? null,
        'entries' => $entries ?? collect(),
    ])
</div>

@include('filament.user.partials.loan-ledger-styles')
