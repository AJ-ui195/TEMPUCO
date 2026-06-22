@php
    use App\Enums\LoanCategory;
    use App\Enums\LoanPurpose;
    use App\Enums\LoanStatus;
    use App\Enums\ModeOfPayment;

    $member = $loan->user;
    $certification = $loan->certification;
    $committee = $loan->committeeDecision;
    $paymentsTotal = (float) $loan->payments->sum('amount');

    $formatMoney = fn (mixed $amount): string => filled($amount) ? '₱'.number_format((float) $amount, 2) : '—';
    $formatDate = fn (mixed $date): string => filled($date) ? $date->format('M j, Y') : '—';
    $formatDateTime = fn (mixed $date): string => filled($date) ? $date->format('M j, Y g:i A') : '—';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Approved loan details') }} — #{{ $loan->id }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 2rem 1rem;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #0f172a;
            background: #f1f5f9;
            line-height: 1.5;
        }

        .sheet {
            width: min(100%, 52rem);
            margin: 0 auto;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        }

        .header {
            padding: 1.75rem 2rem 1.25rem;
            text-align: center;
            border-bottom: 2px solid #0284c7;
            background: linear-gradient(180deg, #f0f9ff 0%, #fff 100%);
        }

        .header-org {
            margin: 0;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #475569;
        }

        .header-address {
            margin: 0.25rem 0 0;
            font-size: 0.75rem;
            color: #64748b;
        }

        .header-title {
            margin: 1rem 0 0;
            font-size: 1.375rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #0c4a6e;
        }

        .header-meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.75rem 1.5rem;
            margin-top: 0.875rem;
            font-size: 0.8125rem;
            color: #475569;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #166534;
            background: #dcfce7;
            border: 1px solid #86efac;
        }

        .content {
            padding: 1.5rem 2rem 2rem;
        }

        .section {
            margin-top: 1.5rem;
        }

        .section:first-child {
            margin-top: 0;
        }

        .section-title {
            margin: 0 0 0.75rem;
            padding-bottom: 0.375rem;
            font-size: 0.8125rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #0369a1;
            border-bottom: 1px solid #e2e8f0;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem 1.25rem;
        }

        .field {
            min-width: 0;
        }

        .field-label {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
        }

        .field-value {
            margin-top: 0.125rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: #0f172a;
            word-break: break-word;
        }

        .field-value.muted {
            font-weight: 500;
            color: #334155;
        }

        .field.span-2 {
            grid-column: span 2;
        }

        .amount-highlight {
            font-size: 1.125rem;
            color: #0284c7;
        }

        .notes {
            margin: 0;
            padding: 0.875rem 1rem;
            border-radius: 0.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 0.875rem;
            white-space: pre-wrap;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
            padding: 1.25rem 2rem 1.75rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        button {
            font: inherit;
            cursor: pointer;
            padding: 0.625rem 1.125rem;
            border-radius: 0.5rem;
            border: 1px solid transparent;
        }

        .btn-primary {
            color: #fff;
            background: #0284c7;
        }

        .btn-secondary {
            color: #334155;
            background: #fff;
            border-color: #cbd5e1;
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .sheet {
                width: 100%;
                border: none;
                border-radius: 0;
                box-shadow: none;
            }

            .actions {
                display: none;
            }
        }

        @media (max-width: 640px) {
            .header,
            .content,
            .actions {
                padding-inline: 1.25rem;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .field.span-2 {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <header class="header">
            <p class="header-org">
                {{ __('Digos City National High School Teachers and Employees Multi-Purpose Cooperative (DICNHS TEMPUCO)') }}
            </p>
            <p class="header-address">{{ __('Rizal Avenue, Digos City') }}</p>
            <h1 class="header-title">{{ __('Approved Loan Details') }}</h1>
            <div class="header-meta">
                <span>{{ __('Reference') }}: #{{ $loan->id }}</span>
                <span>{{ __('Generated') }}: {{ now()->format('M j, Y g:i A') }}</span>
                <span class="status-badge">{{ LoanStatus::Approved->getLabel() }}</span>
            </div>
        </header>

        <div class="content">
            <section class="section">
                <h2 class="section-title">{{ __('Member information') }}</h2>
                <div class="grid">
                    <div class="field">
                        <div class="field-label">{{ __('Name') }}</div>
                        <div class="field-value">{{ $member?->name ?? '—' }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('Email') }}</div>
                        <div class="field-value muted">{{ $member?->email ?? '—' }}</div>
                    </div>
                    <div class="field span-2">
                        <div class="field-label">{{ __('Address') }}</div>
                        <div class="field-value muted">{{ filled($member?->address) ? $member->address : '—' }}</div>
                    </div>
                    @if (filled($member?->cellphone))
                        <div class="field">
                            <div class="field-label">{{ __('Cellphone') }}</div>
                            <div class="field-value muted">{{ $member->cellphone }}</div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">{{ __('Loan details') }}</h2>
                <div class="grid">
                    <div class="field">
                        <div class="field-label">{{ __('Type of loan') }}</div>
                        <div class="field-value">{{ $loan->loan_type ?: '—' }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('Category') }}</div>
                        <div class="field-value muted">
                            {{ $loan->loan_category instanceof LoanCategory ? $loan->loan_category->getLabel() : '—' }}
                        </div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('Loan amount') }}</div>
                        <div class="field-value amount-highlight">{{ $formatMoney($loan->loan_amount) }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('Amount in words') }}</div>
                        <div class="field-value muted">{{ $loan->amountInWords() }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('Loan period') }}</div>
                        <div class="field-value muted">{{ (int) $loan->loan_period_months }} {{ __('months') }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('Monthly installment') }}</div>
                        <div class="field-value">{{ $formatMoney($loan->installment_amount) }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('First payment due') }}</div>
                        <div class="field-value muted">{{ $formatDate($loan->first_payment_due_date) }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('Mode of payment') }}</div>
                        <div class="field-value muted">
                            {{ $loan->mode_of_payment instanceof ModeOfPayment ? $loan->mode_of_payment->getLabel() : '—' }}
                        </div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('Purpose of loan') }}</div>
                        <div class="field-value muted">
                            {{ $loan->purpose_of_loan instanceof LoanPurpose ? $loan->purpose_of_loan->getLabel() : '—' }}
                        </div>
                    </div>
                    @if ($loan->purpose_of_loan === LoanPurpose::Others && filled($loan->purpose_of_loan_other))
                        <div class="field">
                            <div class="field-label">{{ __('Purpose (others)') }}</div>
                            <div class="field-value muted">{{ $loan->purpose_of_loan_other }}</div>
                        </div>
                    @endif
                    <div class="field">
                        <div class="field-label">{{ __('Submitted on') }}</div>
                        <div class="field-value muted">{{ $formatDate($loan->loan_date) }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('Approved on') }}</div>
                        <div class="field-value">{{ $formatDateTime($loan->approved_at) }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('Applicant signed on') }}</div>
                        <div class="field-value muted">{{ $formatDate($loan->applicant_signed_at) }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">{{ __('Total payments received') }}</div>
                        <div class="field-value">{{ $formatMoney($paymentsTotal) }}</div>
                    </div>
                </div>

                @if (filled($loan->application_notes))
                    <div class="field span-2" style="margin-top: 0.875rem;">
                        <div class="field-label">{{ __('Application notes') }}</div>
                        <p class="notes">{{ $loan->application_notes }}</p>
                    </div>
                @endif
            </section>

            @if ($certification)
                <section class="section">
                    <h2 class="section-title">{{ __('Cooperative certification') }}</h2>
                    <div class="grid">
                        <div class="field">
                            <div class="field-label">{{ __('Name of borrower') }}</div>
                            <div class="field-value muted">{{ $certification->borrower_name ?: '—' }}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">{{ __('Date of birth') }}</div>
                            <div class="field-value muted">{{ $formatDate($certification->date_of_birth) }}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">{{ __('Fixed savings deposits') }}</div>
                            <div class="field-value">{{ $formatMoney($certification->fixed_savings_deposits) }}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">{{ __('Standing loan') }}</div>
                            <div class="field-value">{{ $formatMoney($certification->standing_loan) }}</div>
                        </div>
                        <div class="field span-2">
                            <div class="field-label">{{ __('Home address') }}</div>
                            <div class="field-value muted">{{ $certification->home_address ?: '—' }}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">{{ __('Treasurer signed on') }}</div>
                            <div class="field-value muted">{{ $formatDate($certification->treasurer_signed_at) }}</div>
                        </div>
                    </div>
                </section>
            @endif

            @if ($committee)
                <section class="section">
                    <h2 class="section-title">{{ __('Committee approval') }}</h2>
                    <div class="grid">
                        <div class="field">
                            <div class="field-label">{{ __('Meeting date') }}</div>
                            <div class="field-value muted">{{ $formatDate($committee->meeting_date) }}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">{{ __('Amount approved') }}</div>
                            <div class="field-value amount-highlight">{{ $formatMoney($committee->approved_amount) }}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">{{ __('Recorded in minutes') }}</div>
                            <div class="field-value muted">{{ $formatDate($committee->minutes_date) }}</div>
                        </div>
                        @if (filled($committee->conditions_notes))
                            <div class="field span-2">
                                <div class="field-label">{{ __('Conditions / changes') }}</div>
                                <p class="notes">{{ $committee->conditions_notes }}</p>
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </div>

        <div class="actions">
            <button type="button" class="btn-primary" onclick="window.print()">
                {{ __('Print / Save as PDF') }}
            </button>
            <button type="button" class="btn-secondary" onclick="window.close()">
                {{ __('Close') }}
            </button>
        </div>
    </div>

    @if ($autoPrint ?? false)
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
