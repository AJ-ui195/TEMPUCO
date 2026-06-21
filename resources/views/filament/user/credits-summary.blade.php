@php
    use App\Support\MemberPosCredit;

    $credit = new MemberPosCredit($user);
@endphp

<div class="mp-stack">
    <div class="mp-page-intro">
        <h2 class="mp-section-title">{{ __('Your credit balances') }}</h2>
        <p class="mp-section-desc">{{ __('Outstanding amounts from grocery and canteen purchases charged to your account.') }}</p>
    </div>

    <div class="mp-credit-grid">
        <div class="mp-credit-card">
            <div class="mp-stat-card-header">
                <span class="mp-stat-label">{{ __('Grocery') }}</span>
                <span class="mp-stat-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path d="M1 1.75A.75.75 0 0 1 1.75 1h1.082a24.933 24.933 0 0 1 1.268 3.826 1.357 1.357 0 0 0 1.342 1.153h11.246a1 1 0 0 1 .958 1.287 24.928 24.928 0 0 1-1.268 3.826.75.75 0 0 1-.708.513H3.023a.75.75 0 0 1-.708-.513 24.928 24.928 0 0 1-1.268-3.826A1.357 1.357 0 0 0 1.082 2.5H1.75A.75.75 0 0 1 1 1.75ZM6.5 8.25v7.5a.75.75 0 0 0 1.5 0v-7.5a.75.75 0 0 0-1.5 0Zm4.25 0v7.5a.75.75 0 0 0 1.5 0v-7.5a.75.75 0 0 0-1.5 0Zm4.25 0v7.5a.75.75 0 0 0 1.5 0v-7.5a.75.75 0 0 0-1.5 0Z"/></svg>
                </span>
            </div>
            <div class="mp-stat-value">₱{{ number_format($credit->groceryOutstanding(), 2) }}</div>
            <div style="margin-top: 0.875rem;">
                <button
                    type="button"
                    wire:click="mountAction('viewGroceryCreditDetails')"
                    class="mp-btn mp-btn-secondary"
                >
                    {{ __('View details') }}
                </button>
            </div>
        </div>

        <div class="mp-credit-card">
            <div class="mp-stat-card-header">
                <span class="mp-stat-label">{{ __('Canteen') }}</span>
                <span class="mp-stat-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M3 6a3 3 0 0 1 3-3h2.25a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6Zm5.25 6.75a.75.75 0 0 0-1.5 0v2.25a.75.75 0 0 0 1.5 0v-2.25Zm-1.5-3a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75h-.008a.75.75 0 0 1-.75-.75v-.008Zm.75-.75a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75v-.008a.75.75 0 0 0-.75-.75h-.008ZM9 6.75a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H9.75a.75.75 0 0 1-.75-.75V6.75Zm.75-.75a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V6.75a.75.75 0 0 0-.75-.75h-.008ZM12 6.75a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75h-.008a.75.75 0 0 1-.75-.75V6.75Zm.75-.75a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V6.75a.75.75 0 0 0-.75-.75h-.008Z" clip-rule="evenodd"/></svg>
                </span>
            </div>
            <div class="mp-stat-value">₱{{ number_format($credit->canteenOutstanding(), 2) }}</div>
            <div style="margin-top: 0.875rem;">
                <button
                    type="button"
                    wire:click="mountAction('viewCanteenCreditDetails')"
                    class="mp-btn mp-btn-secondary"
                >
                    {{ __('View details') }}
                </button>
            </div>
        </div>
    </div>

    <div class="mp-total-bar">
        <span>{{ __('Total outstanding') }}</span>
        <span class="mp-total-bar-value">₱{{ number_format($credit->totalOutstanding(), 2) }}</span>
    </div>

    <div class="mp-actions-row">
        <button
            type="button"
            wire:click="mountAction('viewOutstandingBalances')"
            class="mp-btn mp-btn-primary"
        >
            {{ __('View full breakdown') }}
        </button>
    </div>
</div>
