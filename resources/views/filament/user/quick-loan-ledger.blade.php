@php
    use App\Models\Loan;

    $loanLabel = function (Loan $loan): string {
        $date = $loan->loan_date?->format('Y-m-d') ?? '—';
        $amount = '₱'.number_format((float) $loan->loan_amount, 2);
        $status = $loan->status?->getLabel() ?? '—';

        return $date.' — '.$amount.' — '.$status;
    };
@endphp

<div class="ll-page">
    <label class="ll-field">
        <span>{{ __('Quick loan') }}</span>
        <select wire:model.live="quickLoanId">
            <option value="">{{ $quickLoans->isEmpty() ? __('No quick loans yet') : __('Select a quick loan') }}</option>
            @foreach ($quickLoans as $loan)
                <option value="{{ $loan->getKey() }}">{{ $loanLabel($loan) }}</option>
            @endforeach
        </select>
    </label>

    @include('filament.user.canteen-individual-ledger', [
        'user' => $user,
        'entries' => collect(),
        'department' => __('Quick Loan'),
    ])
</div>

@include('filament.user.partials.loan-ledger-styles')
