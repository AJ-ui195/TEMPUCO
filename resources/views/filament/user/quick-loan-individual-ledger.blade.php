@php
    $amount = function (float $value): string {
        if (abs($value) < 0.005) {
            return '';
        }

        return number_format($value, 2);
    };

    $loan = $loan ?? null;
    $entries = $entries ?? collect();
@endphp

<div class="ll-sheet">
    @include('filament.user.partials.ledger-letterhead', [
        'ledgerSubtitle' => __('Loan account'),
    ])

    <div class="ll-member ll-member-grid">
        <div><span>{{ __('Name') }}:</span> {{ $user->name }}</div>
        <div><span>{{ __('Type of loan') }}:</span> {{ __('QUICK LOAN') }}</div>
        <div><span>{{ __('Term') }}:</span> {{ $loan?->loan_period_months ?? 1 }}</div>
        <div><span>{{ __('Maturity') }}:</span> {{ $loan?->first_payment_due_date?->format('n-j-Y') ?? '' }}</div>
        <div><span>{{ __('Amortization') }}:</span> {{ $loan && (float) $loan->installment_amount > 0 ? number_format((float) $loan->installment_amount, 2) : '' }}</div>
        <div><span>{{ __('Mode of payment') }}:</span> {{ $loan?->mode_of_payment?->getLabel() ?? '' }}</div>
    </div>

    <div class="ll-table-wrap">
        <table class="ll-grid">
            <thead>
                <tr>
                    <th rowspan="2">{{ __('Date') }}</th>
                    <th colspan="2">{{ __('Reference') }}</th>
                    <th rowspan="2">{{ __('Amount released') }}</th>
                    <th colspan="2">{{ __('Interest') }}</th>
                    <th rowspan="2">{{ __('Payment') }}</th>
                    <th rowspan="2">{{ __('Balance') }}</th>
                    <th colspan="2">{{ __('Surcharge') }}</th>
                    <th rowspan="2">{{ __('Remarks') }}</th>
                </tr>
                <tr>
                    <th>{{ __('O.R.') }}</th>
                    <th>{{ __('Voucher #') }}</th>
                    <th>{{ __('1%') }}</th>
                    <th>{{ __('%') }}</th>
                    <th>{{ __('Payment') }}</th>
                    <th>{{ __('Balance') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>{{ $entry['date']->format('n-j-Y') }}</td>
                        <td>{{ $entry['or'] }}</td>
                        <td>{{ $entry['voucher'] }}</td>
                        <td class="num">{{ $amount((float) $entry['released']) }}</td>
                        <td class="num">{{ $amount((float) $entry['interest']) }}</td>
                        <td></td>
                        <td class="num">{{ $amount((float) $entry['payment']) }}</td>
                        <td class="num">{{ number_format((float) $entry['balance'], 2) }}</td>
                        <td class="num">{{ $amount((float) $entry['surcharge_payment']) }}</td>
                        <td class="num">{{ $amount((float) $entry['surcharge_balance']) }}</td>
                        <td>{{ $entry['remarks'] }}</td>
                    </tr>
                @empty
                    @for ($blankRow = 0; $blankRow < 8; $blankRow++)
                        <tr>
                            <td>&nbsp;</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                @endforelse
            </tbody>
        </table>
    </div>
</div>
