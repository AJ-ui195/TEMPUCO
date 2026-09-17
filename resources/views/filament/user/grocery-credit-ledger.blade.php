@include('filament.user.canteen-individual-ledger', [
    'user' => $user,
    'entries' => $entries,
    'department' => __('Grocery Department'),
])

@include('filament.user.partials.loan-ledger-styles')
