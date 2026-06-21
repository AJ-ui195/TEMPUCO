@php
    use App\Support\MemberPosCredit;

    $credit = new MemberPosCredit($user);
@endphp

<div class="mp-modal-stack">
    <p class="mp-modal-intro">
        {{ __('Amounts you still owe from grocery and canteen purchases charged to your account.') }}
    </p>

    <div class="mp-modal-credit-row">
        <div>
            <div class="mp-stat-label">{{ __('Grocery') }}</div>
            <div class="mp-stat-value" style="margin-top: 0.25rem; font-size: 1.25rem;">
                ₱{{ number_format($credit->groceryOutstanding(), 2) }}
            </div>
        </div>
        <button
            type="button"
            wire:click="mountAction('viewGroceryCreditDetails')"
            class="mp-btn mp-btn-primary"
        >
            {{ __('View details') }}
        </button>
    </div>

    <div class="mp-modal-credit-row">
        <div>
            <div class="mp-stat-label">{{ __('Canteen') }}</div>
            <div class="mp-stat-value" style="margin-top: 0.25rem; font-size: 1.25rem;">
                ₱{{ number_format($credit->canteenOutstanding(), 2) }}
            </div>
        </div>
        <button
            type="button"
            wire:click="mountAction('viewCanteenCreditDetails')"
            class="mp-btn mp-btn-primary"
        >
            {{ __('View details') }}
        </button>
    </div>

    <div class="mp-total-bar">
        <span>{{ __('Total outstanding') }}</span>
        <span class="mp-total-bar-value">₱{{ number_format($credit->totalOutstanding(), 2) }}</span>
    </div>
</div>
