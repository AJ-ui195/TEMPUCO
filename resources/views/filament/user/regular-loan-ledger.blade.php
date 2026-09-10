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
        <span>{{ __('Regular loan') }}</span>
        <select wire:model.live="regularLoanId">
            <option value="">{{ $regularLoans->isEmpty() ? __('No regular loans yet') : __('Select a regular loan') }}</option>
            @foreach ($regularLoans as $loan)
                <option value="{{ $loan->getKey() }}">{{ $loanLabel($loan) }}</option>
            @endforeach
        </select>
    </label>

    @include('filament.user.regular-loan-schedule', [
        'schedule' => $schedule,
        'borrowerName' => $borrowerName,
        'blankAmounts' => $blankAmounts ?? false,
    ])
</div>

@include('filament.user.partials.loan-ledger-styles')
