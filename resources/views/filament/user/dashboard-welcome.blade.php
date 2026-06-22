@php
    use App\Enums\LoanStatus;
    use App\Filament\User\Pages\ApplyLoan;
    use App\Filament\User\Pages\Credits;
    use App\Filament\User\Pages\MemberQrCodePage;
    use App\Support\MemberPosCredit;

    $credit = new MemberPosCredit($user);
    $totalOutstanding = $credit->totalOutstanding();
    $loansCount = $user->loans()->count();
    $pendingLoans = $user->loans()->where('status', LoanStatus::Pending)->count();
    $hour = (int) now()->format('G');
    $greeting = match (true) {
        $hour < 12 => __('Good morning'),
        $hour < 18 => __('Good afternoon'),
        default => __('Good evening'),
    };
@endphp

<div class="mp-stack">
    <div class="mp-hero">
        <p class="mp-hero-eyebrow">{{ __('Members Portal') }}</p>
        <h1 class="mp-hero-title">{{ $greeting }}, {{ $user->name }}</h1>
        <p class="mp-hero-subtitle">
            {{ __('Manage your cooperative account, track credits, apply for loans, and access your member QR code — all in one place.') }}
        </p>
        <div class="mp-hero-meta">
            @if (filled($user->email))
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z"/><path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z"/></svg>
                    {{ $user->email }}
                </span>
            @endif
            @if (filled($user->cellphone))
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 0 1 2.43 8.326 13.019 13.019 0 0 1 2 5V3.5Z" clip-rule="evenodd"/></svg>
                    {{ $user->cellphone }}
                </span>
            @endif
        </div>
    </div>

    <div>
        <h2 class="mp-section-title">{{ __('At a glance') }}</h2>
        <p class="mp-section-desc">{{ __('Your current account summary.') }}</p>
        <div class="mp-stat-grid" style="margin-top: 1rem;">
            <div class="mp-stat-card">
                <div class="mp-stat-card-header">
                    <span class="mp-stat-label">{{ __('Total outstanding') }}</span>
                    <span class="mp-stat-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M1 2.75A.75.75 0 0 1 1.75 2h16.5a.75.75 0 0 1 0 1.5H18v8.75A2.75 2.75 0 0 1 15.25 15h-1.072l.798 3.065a.75.75 0 0 1-1.452.38L13.41 15H6.59l-.114.445a.75.75 0 0 1-1.452-.38L5.823 15H4.75A2.75 2.75 0 0 1 2 12.25V3.5h-.25A.75.75 0 0 1 1 2.75ZM7.5 7a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2H8.5a1 1 0 0 1-1-1Zm-1 3.75a1 1 0 0 1 1-1h5a1 1 0 1 1 0 2h-5a1 1 0 0 1-1-1Z" clip-rule="evenodd"/></svg>
                    </span>
                </div>
                <div class="mp-stat-value">₱{{ number_format($totalOutstanding, 2) }}</div>
                <p class="mp-stat-hint">{{ __('Grocery & canteen credits') }}</p>
            </div>

            <div class="mp-stat-card">
                <div class="mp-stat-card-header">
                    <span class="mp-stat-label">{{ __('My loans') }}</span>
                    <span class="mp-stat-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M4.25 2A2.25 2.25 0 0 0 2 4.25v11.5A2.25 2.25 0 0 0 4.25 18h11.5A2.25 2.25 0 0 0 18 15.75V4.25A2.25 2.25 0 0 0 15.75 2H4.25Zm4.03 6.28a.75.75 0 0 0-1.06-1.06L4.97 9.47a.75.75 0 0 0 0 1.06l2.25 2.25a.75.75 0 0 0 1.06-1.06L6.56 10l1.72-1.72Zm4.44-1.06a.75.75 0 1 0-1.06 1.06L13.44 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06l2.25-2.25a.75.75 0 0 0 0-1.06l-2.25-2.25Z" clip-rule="evenodd"/></svg>
                    </span>
                </div>
                <div class="mp-stat-value">{{ $loansCount }}</div>
                <p class="mp-stat-hint">
                    @if ($pendingLoans > 0)
                        {{ trans_choice(':count pending review|:count pending reviews', $pendingLoans, ['count' => $pendingLoans]) }}
                    @else
                        {{ __('Total loan records') }}
                    @endif
                </p>
            </div>

            <div class="mp-stat-card">
                <div class="mp-stat-card-header">
                    <span class="mp-stat-label">{{ __('Member ID') }}</span>
                    <span class="mp-stat-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM6 8a2 2 0 1 1 4 0 2 2 0 0 1-4 0ZM1.49 15.326a.78.78 0 0 1-.358-.442 3 3 0 0 1 4.308-3.516 6.484 6.484 0 0 0-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 0 1-2.07-.655ZM16.44 15.98a4.97 4.97 0 0 0 2.07-.654.78.78 0 0 0 .357-.442 3 3 0 0 0-4.308-3.517 6.484 6.484 0 0 1 1.907 3.96 2.32 2.32 0 0 1-.026.654ZM18 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM5.304 16.19a.844.844 0 0 1-.277-.71 5 5 0 0 1 9.947 0 .843.843 0 0 1-.277.71A6.975 6.975 0 0 1 10 18a6.974 6.974 0 0 1-4.696-1.81Z" clip-rule="evenodd"/></svg>
                    </span>
                </div>
                <div class="mp-stat-value">#{{ $user->id }}</div>
                <p class="mp-stat-hint">{{ __('Use your QR code at the cooperative') }}</p>
            </div>
        </div>
    </div>

    <div>
        <h2 class="mp-section-title">{{ __('Quick actions') }}</h2>
        <p class="mp-section-desc">{{ __('Jump to the most common member tasks.') }}</p>
        <div class="mp-quick-grid" style="margin-top: 1rem;">
            <a href="{{ Credits::getUrl() }}" class="mp-quick-link">
                <span class="mp-quick-link-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M1 2.75A.75.75 0 0 1 1.75 2h16.5a.75.75 0 0 1 0 1.5H18v8.75A2.75 2.75 0 0 1 15.25 15h-1.072l.798 3.065a.75.75 0 0 1-1.452.38L13.41 15H6.59l-.114.445a.75.75 0 0 1-1.452-.38L5.823 15H4.75A2.75 2.75 0 0 1 2 12.25V3.5h-.25A.75.75 0 0 1 1 2.75Z" clip-rule="evenodd"/></svg>
                </span>
                <span>
                    <span class="mp-quick-link-title">{{ __('View credits') }}</span>
                    <span class="mp-quick-link-desc">{{ __('Check grocery and canteen balances.') }}</span>
                </span>
            </a>

            <a href="{{ MemberQrCodePage::getUrl() }}" class="mp-quick-link">
                <span class="mp-quick-link-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M3 4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4Zm2 2V5h2v1H5ZM3 12a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-4Zm2 2v-1h2v1H5ZM11 4a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1h-4Zm2 2V5h2v1h-2Z" clip-rule="evenodd"/><path d="M11 12a1 1 0 0 1 1-1h1v1h-1a1 1 0 0 1-1-1Zm2 2h-1v1h1v-1Zm-2 0a1 1 0 0 0-1 1v1h1v-2h-1Zm2 2h1v1h-1v-1Zm2-2a1 1 0 0 1 1-1h1v3h-1a1 1 0 0 1-1-1v-1Zm1 1v-1h1v1h-1Z"/></svg>
                </span>
                <span>
                    <span class="mp-quick-link-title">{{ __('My QR code') }}</span>
                    <span class="mp-quick-link-desc">{{ __('Show or print your member identification.') }}</span>
                </span>
            </a>

            <a href="{{ ApplyLoan::getUrl() }}" class="mp-quick-link">
                <span class="mp-quick-link-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.122-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm2.25 6a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5h-6.5Zm0 3.5a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5h-6.5Z" clip-rule="evenodd"/></svg>
                </span>
                <span>
                    <span class="mp-quick-link-title">{{ __('Apply for a loan') }}</span>
                    <span class="mp-quick-link-desc">{{ __('Submit a regular or quick loan application.') }}</span>
                </span>
            </a>
        </div>
    </div>
</div>
