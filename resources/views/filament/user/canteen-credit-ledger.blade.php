@include('filament.user.canteen-individual-ledger', [
    'user' => $user,
    'entries' => $entries,
])

@include('filament.user.partials.loan-ledger-styles')
