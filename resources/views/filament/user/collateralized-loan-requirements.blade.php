@php
    /** @var \App\Models\Member $member */
    $rateLabel = $member->isRetiree()
        ? __('1% monthly (retiree)')
        : __('2% monthly (regular member)');
@endphp

<div class="mp-collateral-modal">
    <h3>{{ __('Requirements') }}</h3>
    <p>{{ __('The borrower shall submit:') }}</p>
    <ol>
        <li>{{ __('Collateralized Loan Application Form') }}</li>
        <li>{{ __('Deed of Sale with Right to Repurchase') }}</li>
        <li>{{ __('Promissory Note') }}</li>
        <li>{{ __('Updated Tax Declaration') }}</li>
        <li>{{ __('Other documents required by Management') }}</li>
    </ol>

    <h3>{{ __('Loan terms') }}</h3>
    <div class="mp-table-wrap">
        <table class="mp-table">
            <thead>
                <tr>
                    <th>{{ __('Particulars') }}</th>
                    <th>{{ __('Provision') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ __('Term') }}</td>
                    <td>{{ __('12 to 24 months') }}</td>
                </tr>
                <tr>
                    <td>{{ __('Interest') }}</td>
                    <td>{{ $rateLabel }}</td>
                </tr>
                <tr>
                    <td>{{ __('Insurance') }}</td>
                    <td>{{ __('Required') }}</td>
                </tr>
                <tr>
                    <td>{{ __('Maximum loan') }}</td>
                    <td>₱{{ number_format(\App\Support\CollateralizedLoanRules::MAX_AMOUNT, 2) }}</td>
                </tr>
                <tr>
                    <td>{{ __('Amortization') }}</td>
                    <td>{{ __('Equal monthly') }}</td>
                </tr>
                <tr>
                    <td>{{ __('Interest basis') }}</td>
                    <td>{{ __('Diminishing balance') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
