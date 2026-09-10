@php
    $amount = function (float $value): string {
        if (abs($value) < 0.005) {
            return '';
        }

        return number_format($value, 2);
    };
@endphp

<div class="ll-sheet">
    <header class="ll-sheet-head">
        <div class="ll-sheet-strong">DICNHS TEMPUCO</div>
        <h3>{{ __('Individual ledger') }}</h3>
        <div>{{ $department ?? __('Canteen Department') }}</div>
    </header>

    <div class="ll-member">
        <div><span>{{ __('Name') }}:</span> {{ $user->name }}</div>
        <div><span>{{ __('Address') }}:</span> {{ $user->address }}</div>
    </div>

    <div class="ll-table-wrap">
        <table class="ll-grid">
            <thead>
                <tr>
                    <th rowspan="2">{{ __('Date') }}</th>
                    <th>{{ __('Reference') }}</th>
                    <th colspan="2">{{ __('Sales') }}</th>
                    <th rowspan="2">{{ __('Payment') }}</th>
                    <th rowspan="2">{{ __('Balance') }}</th>
                    <th rowspan="2">{{ __('Penalty') }}</th>
                    <th rowspan="2">{{ __('Remarks') }}</th>
                </tr>
                <tr>
                    <th>{{ __('Official receipt no.') }}</th>
                    <th>{{ __('Cash') }}</th>
                    <th>{{ __('Account') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>{{ $entry['date']->format('n-j-Y') }}</td>
                        <td>{{ $entry['type'] === 'payment' ? $entry['reference'] : '' }}</td>
                        <td></td>
                        <td class="num">{{ $amount((float) $entry['charge']) }}</td>
                        <td class="num">{{ $amount((float) $entry['payment']) }}</td>
                        <td class="num">{{ number_format((float) $entry['balance'], 2) }}</td>
                        <td></td>
                        <td></td>
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
                        </tr>
                    @endfor
                @endforelse
            </tbody>
        </table>
    </div>
</div>
