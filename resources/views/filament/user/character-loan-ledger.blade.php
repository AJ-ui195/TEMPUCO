<div class="ll-page">
    @include('filament.user.character-individual-ledger', [
        'user' => $user,
        'entries' => $entries ?? collect(),
    ])
</div>

@include('filament.user.partials.loan-ledger-styles')
