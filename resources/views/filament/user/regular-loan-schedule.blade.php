@php
    $blankAmounts = $blankAmounts ?? false;
    $money = function (?float $amount) use ($blankAmounts): string {
        if ($blankAmounts || $amount === null) {
            return '';
        }

        return number_format($amount, 2);
    };
    $percent = fn (float $rate): string => number_format($rate * 100, 3).'%';
    $chargeLabels = [
        'credit_loan_insurance' => __('Credit loan insurance'),
        'processing_fee' => __('Processing fee'),
        'service_fee' => __('Service fee'),
        'notarial_fee' => __('Notarial fee'),
        'verification_fee' => __('Verification fee'),
    ];
@endphp

<div class="apds">
    <header class="apds-head">
        <div>{{ __('Republic of the Philippines') }}</div>
        <div>{{ __('Department of Education') }}</div>
        <div class="apds-strong">{{ __('Automatic Payroll Deduction System (APDS) Program') }}</div>
        <div class="apds-coop">{{ __('Digos City National High School Teachers and Employees Multi-Purpose Cooperative') }}</div>
        <h3>{{ $blankAmounts
            ? __('Effective interest calculation model')
            : __('Effective interest calculation model for a (:years)-year loan', ['years' => $schedule->termYearsLabel()]) }}</h3>
        <div class="apds-method">{{ __('Declining/diminishing balance method') }}</div>
    </header>

    <table class="apds-inputs">
        <colgroup>
            <col class="c-no">
            <col class="c-label">
            <col class="c-value">
            <col class="c-gap">
            <col class="c-rate">
            <col class="c-period">
            <col class="c-eq">
            <col class="c-pct">
        </colgroup>
        <tbody>
            <tr>
                <td>1</td>
                <td>{{ __('Principal amount (in Php)') }}</td>
                <td class="num">{{ $blankAmounts ? '' : $money($schedule->principal) }}</td>
                <td></td>
                <td>{{ __('Contractual interest rate') }}</td>
                <td>{{ __('Per annum') }}</td>
                <td class="eq">=</td>
                <td class="num">{{ $percent($schedule->annualInterestRate) }}</td>
            </tr>
            <tr>
                <td>2</td>
                <td>{{ __('Loan term (in years)') }}</td>
                <td class="num">{{ $blankAmounts ? '' : $schedule->termYearsLabel() }}</td>
                <td></td>
                <td></td>
                <td>{{ __('Per month') }}</td>
                <td class="eq">=</td>
                <td class="num">{{ $percent($schedule->monthlyInterestRate) }}</td>
            </tr>
            <tr>
                <td>3</td>
                <td>{{ __('No. of installments (in months)') }}</td>
                <td class="num">{{ $blankAmounts ? '' : $schedule->installments }}</td>
                <td></td>
                <td>{{ __('Nominal interest rate') }}</td>
                <td>{{ __('Per month') }}</td>
                <td class="eq">=</td>
                <td class="num">{{ $percent($schedule->monthlyInterestRate) }}</td>
            </tr>
            <tr>
                <td>4</td>
                <td>{{ __('Grace period (in months)') }}</td>
                <td class="num"></td>
                <td></td>
                <td>{{ __('Effective interest rate (EIR)') }}</td>
                <td>{{ __('Per annum') }}</td>
                <td class="eq">=</td>
                <td class="num">{{ $blankAmounts ? '' : $percent($schedule->annualEir) }}</td>
            </tr>
            <tr>
                <td>5</td>
                <td>{{ __('No. of periods (in months)') }}</td>
                <td class="num">{{ $blankAmounts ? '' : $schedule->periods }}</td>
                <td></td>
                <td></td>
                <td>{{ __('Per month') }}</td>
                <td class="eq">=</td>
                <td class="num">{{ $blankAmounts ? '' : $percent($schedule->monthlyEir) }}</td>
            </tr>
            <tr>
                <td>6</td>
                <td>{{ __('Other charges') }}{{ $schedule->isSecondApdsAccount ? ' ('.__('2nd APDS').')' : '' }}</td>
                <td class="num">{{ $percent($schedule->otherChargesRate) }}</td>
                <td colspan="4"></td>
            </tr>
            @foreach ($schedule->otherChargeLines as $line)
                <tr>
                    <td></td>
                    <td class="indent">{{ $chargeLabels[$line['key']] ?? $line['key'] }}</td>
                    <td class="num">{{ $percent($line['rate']) }}</td>
                    <td colspan="5"></td>
                </tr>
            @endforeach
            @if (! $blankAmounts && $schedule->capitalBuildUpRetention > 0)
                <tr>
                    <td></td>
                    <td class="indent">{{ __('Capital build-up retention (2nd APDS)') }}</td>
                    <td class="num">{{ $money($schedule->capitalBuildUpRetention) }}</td>
                    <td colspan="5"></td>
                </tr>
            @endif
            <tr>
                <td>7</td>
                <td>{{ __('Monthly installment') }}</td>
                <td class="num highlight">{{ $blankAmounts ? '' : 'PHP '.$money($schedule->monthlyInstallment) }}</td>
                <td colspan="5"></td>
            </tr>
        </tbody>
    </table>

    <div class="apds-scroll">
        <table class="apds-grid">
            <thead>
                <tr>
                    <th colspan="2">{{ __('Monthly') }}</th>
                    <th rowspan="2">{{ __('Gross loan') }}</th>
                    <th rowspan="2">{{ __('Principal') }}</th>
                    <th rowspan="2">{{ __('Interest') }}</th>
                    <th rowspan="2">{{ __('Other charges') }}</th>
                    <th rowspan="2">{{ __('Net proceeds') }}</th>
                    <th rowspan="2">{{ __('Cash flows') }}</th>
                    <th rowspan="2">{{ __('Outstanding balance') }}</th>
                </tr>
                <tr>
                    <th colspan="2">{{ __('Period') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($schedule->rows as $row)
                    <tr>
                        <td class="num" colspan="2">{{ $row['period'] }}</td>
                        <td class="num">{{ $money($row['gross_loan']) }}</td>
                        <td class="num">{{ $money($row['principal']) }}</td>
                        <td class="num">{{ $money($row['interest']) }}</td>
                        <td class="num">{{ $money($row['other_charges']) }}</td>
                        <td class="num">{{ $money($row['net_proceeds']) }}</td>
                        <td class="num">{{ $blankAmounts || $row['cash_flow'] === null ? ($blankAmounts ? '' : '-') : '('.number_format($row['cash_flow'], 2).')' }}</td>
                        <td class="num">{{ $row['outstanding'] == 0 && $row['period'] > 0 ? '-' : $money($row['outstanding']) }}</td>
                    </tr>
                @endforeach
                <tr class="apds-total">
                    <td colspan="2">{{ __('Total') }}</td>
                    <td></td>
                    <td class="num">{{ $money($schedule->totalPrincipal) }}</td>
                    <td class="num">{{ $money($schedule->totalInterest) }}</td>
                    <td class="num">{{ $money($schedule->otherCharges) }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <footer class="apds-sign">
        <div class="apds-sign-block">
            <div>{{ __('Prepared by') }}:</div>
            <strong>LORNA H. BRUTAS</strong>
            <div>{{ __('Bookkeeper') }}</div>
        </div>
        <div class="apds-sign-block">
            <div>{{ __('Certified by') }}:</div>
            <strong>JESSAMAE B. DEL ROSARIO</strong>
            <div>{{ __('General Manager') }}</div>
        </div>
        <div class="apds-sign-borrower">
            <div class="apds-zero">0.000%</div>
            <div class="apds-line">{{ $borrowerName }}</div>
            <div>{{ __('Signature of borrower over printed name') }}</div>
        </div>
    </footer>
</div>

<style>
    .apds {
        background: #fff;
        color: #111;
        border: 1px solid #bfbfbf;
        padding: 1rem 1.1rem 1.25rem;
        font-family: Calibri, "Segoe UI", Arial, sans-serif;
        font-size: 12px;
    }

    .apds-head {
        text-align: center;
        line-height: 1.25;
        margin-bottom: 0.85rem;
    }

    .apds-head h3,
    .apds-strong,
    .apds-method,
    .apds-coop {
        margin: 0.15rem 0 0;
        font-weight: 700;
        text-transform: uppercase;
    }

    .apds-head h3 {
        font-size: 13px;
        text-decoration: underline;
    }

    .apds-inputs,
    .apds-grid {
        width: 100%;
        border-collapse: collapse;
    }

    .apds-inputs td,
    .apds-grid th,
    .apds-grid td {
        border: 1px solid #7f7f7f;
        padding: 1px 4px;
        height: 18px;
        vertical-align: middle;
    }

    .apds-inputs {
        margin-bottom: 0.65rem;
        table-layout: fixed;
    }

    .c-no { width: 28px; }
    .c-label { width: 28%; }
    .c-value { width: 110px; }
    .c-gap { width: 18px; }
    .c-rate { width: 22%; }
    .c-period { width: 78px; }
    .c-eq { width: 18px; }
    .c-pct { width: 72px; }

    .apds-inputs td:nth-child(4),
    .apds-inputs td:nth-child(5),
    .apds-inputs td:nth-child(6),
    .apds-inputs td:nth-child(7),
    .apds-inputs td:nth-child(8) {
        border-color: #d0d0d0;
    }

    .num {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .eq {
        text-align: center;
    }

    .indent {
        padding-left: 1.1rem !important;
    }

    .highlight {
        background: #f4b183;
        font-weight: 700;
    }

    .apds-scroll {
        overflow-x: auto;
    }

    .apds-grid th {
        background: #f3f3f3;
        font-size: 11px;
        font-weight: 700;
        text-align: center;
    }

    .apds-grid td {
        font-size: 11px;
    }

    .apds-total td {
        font-weight: 700;
    }

    .apds-sign {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem 2rem;
        margin-top: 1.35rem;
        font-size: 11px;
    }

    .apds-sign-block strong,
    .apds-line {
        display: block;
        margin-top: 1.6rem;
        font-weight: 700;
        text-transform: uppercase;
        border-top: 1px solid #111;
        padding-top: 0.2rem;
        max-width: 16rem;
    }

    .apds-sign-borrower {
        grid-column: 1 / -1;
        max-width: 22rem;
    }

    .apds-zero {
        text-align: right;
        max-width: 16rem;
        font-size: 10px;
    }

    @media (max-width: 800px) {
        .apds-sign {
            grid-template-columns: 1fr;
        }
    }
</style>
