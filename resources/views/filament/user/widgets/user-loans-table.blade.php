@php
    use App\Enums\LoanStatus;

    $loans = $this->loans();
@endphp

<div class="mp-loans-widget">
    <div>
        <h2 class="mp-section-title">{{ __('My loans') }}</h2>
        <p class="mp-section-desc">{{ __('Track the status and details of your loan applications.') }}</p>
    </div>

    @if ($loans->isEmpty())
        <div class="mp-empty">
            <p style="margin: 0 0 0.25rem; font-weight: 600;">{{ __('No loans yet') }}</p>
            <p style="margin: 0;">{{ __('When you have active or past loans, they will appear here.') }}</p>
        </div>
    @else
        <div class="mp-table-wrap">
            <table class="mp-table">
                <thead>
                    <tr>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Loan type') }}</th>
                        <th>{{ __('Loan amount') }}</th>
                        <th>{{ __('Loan period') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($loans as $loan)
                        @php
                            $status = $loan->status instanceof LoanStatus
                                ? $loan->status
                                : LoanStatus::tryFrom((string) $loan->status);
                            $statusKey = $status?->value ?? 'pending';
                            $approved = $this->isApproved($loan);
                        @endphp
                        <tr>
                            <td>
                                <span class="mp-status mp-status--{{ $statusKey }}">
                                    {{ $status?->getLabel() ?? (string) $loan->status }}
                                </span>
                            </td>
                            <td>{{ $loan->loan_type }}</td>
                            <td>₱{{ number_format((float) $loan->loan_amount, 2) }}</td>
                            <td>{{ (int) $loan->loan_period_months }} {{ __('months') }}</td>
                            <td>{{ $loan->loan_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="mp-loans-actions">
                                @if ($approved)
                                    <a href="{{ $this->printUrl($loan) }}" target="_blank" rel="noopener noreferrer" class="mp-btn mp-btn-secondary">
                                        {{ __('Print details') }}
                                    </a>
                                @else
                                    <span class="mp-btn mp-btn-secondary mp-btn-disabled" title="{{ __('Available only for approved loans.') }}">
                                        {{ __('Print details') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
