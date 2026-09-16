@php
    $amount = function (float $value): string {
        if (abs($value) < 0.005) {
            return '';
        }

        return number_format($value, 2);
    };

    $mode = $loan?->mode_of_payment;
    $modeLabel = $mode instanceof \App\Enums\ModeOfPayment ? $mode->getLabel() : '';
    $termMonths = (int) ($loan?->loan_period_months ?? 0);
    $maturity = null;
    if ($loan) {
        $start = $loan->first_payment_due_date ?? $loan->loan_date;
        if ($start && $termMonths > 0) {
            $maturity = \Illuminate\Support\Carbon::parse($start)->addMonthsNoOverflow(max(0, $termMonths - 1));
        }
    }
    $loanTypeLabel = \App\Support\LoanTypes::isSalary2((string) ($loan?->loan_type ?? ''))
        ? __('Salary loan 2')
        : __('Salary loan 1');
@endphp

<div class="ll-sheet">
    @include('filament.user.partials.ledger-letterhead', [
        'ledgerSubtitle' => __('Loan account'),
    ])

    <div class="ll-member ll-member-grid">
        <div><span>{{ __('Name') }}:</span> {{ $user->name }}</div>
        <div><span>{{ __('Maturity') }}:</span> {{ $maturity?->format('n-j-Y') ?? '' }}</div>
        <div><span>{{ __('Type of loan') }}:</span> {{ $loan ? $loanTypeLabel : __('Salary loan') }}</div>
        <div><span>{{ __('Amortization') }}:</span> {{ $loan ? number_format((float) $loan->installment_amount, 2) : '' }}</div>
        <div><span>{{ __('Term') }}:</span> {{ $termMonths > 0 ? $termMonths.' '.__('mos.') : '' }}</div>
        <div><span>{{ __('Mode of payment') }}:</span> {{ $modeLabel }}</div>
    </div>

    <div class="ll-table-wrap">
        <table class="ll-grid">
            <thead>
                <tr>
                    <th rowspan="2">{{ __('Date') }}</th>
                    <th colspan="2">{{ __('Reference') }}</th>
                    <th colspan="2">{{ __('Amount') }}</th>
                    <th rowspan="2">{{ __('Payment') }}</th>
                    <th rowspan="2">{{ __('Balance') }}</th>
                    <th rowspan="2">{{ __('Surcharge') }}</th>
                    <th rowspan="2">{{ __('Remarks') }}</th>
                </tr>
                <tr>
                    <th>{{ __('O.R.') }}</th>
                    <th>{{ __('Voucher #') }}</th>
                    <th>{{ __('Released') }}</th>
                    <th>{{ __('Interest') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>{{ $entry['date']->format('n-j-Y') }}</td>
                        <td>{{ $entry['or'] ?? '' }}</td>
                        <td>{{ $entry['voucher'] ?? '' }}</td>
                        <td class="num">{{ $amount((float) ($entry['released'] ?? 0)) }}</td>
                        <td class="num">
                            @php $interestVal = (float) ($entry['interest'] ?? 0); @endphp
                            @if (abs($interestVal) >= 0.005)
                                {{ ! empty($entry['interest_in_parens']) ? '('.$amount($interestVal).')' : $amount($interestVal) }}
                            @endif
                        </td>
                        <td class="num">
                            @php $paymentVal = (float) ($entry['payment'] ?? 0); @endphp
                            @if (abs($paymentVal) >= 0.005)
                                {{ $amount($paymentVal) }}
                            @endif
                        </td>
                        <td class="num">
                            @if (! empty($entry['balance_blank']))
                                —
                            @else
                                {{ number_format((float) ($entry['balance'] ?? 0), 2) }}
                            @endif
                        </td>
                        <td class="num">{{ $amount((float) ($entry['surcharge'] ?? 0)) }}</td>
                        <td>{{ $entry['remarks'] ?? '' }}</td>
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
                        </tr>
                    @endfor
                @endforelse
            </tbody>
        </table>
    </div>
</div>
