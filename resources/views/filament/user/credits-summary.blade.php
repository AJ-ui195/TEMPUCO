@php
    use App\Support\MemberPosCredit;

    $credit = new MemberPosCredit($user);
@endphp

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: 1rem;">
    <div style="padding: 1rem 1.25rem; border-radius: 0.75rem; border: 1px solid rgba(148, 163, 184, 0.35); background: rgba(255, 255, 255, 0.6);">
        <div style="font-size: 0.8125rem; font-weight: 600; color: #0284c7;">{{ __('Grocery') }}</div>
        <div style="font-size: 1.5rem; font-weight: 700; margin-top: 0.375rem;">
            ₱{{ number_format($credit->groceryOutstanding(), 2) }}
        </div>
        <button
            type="button"
            wire:click="mountAction('viewGroceryCreditDetails')"
            style="margin-top: 0.75rem; padding: 0.375rem 0.75rem; font-size: 0.8125rem; font-weight: 600; color: #0284c7; background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.25); border-radius: 0.5rem; cursor: pointer;"
        >
            {{ __('View details') }}
        </button>
    </div>

    <div style="padding: 1rem 1.25rem; border-radius: 0.75rem; border: 1px solid rgba(148, 163, 184, 0.35); background: rgba(255, 255, 255, 0.6);">
        <div style="font-size: 0.8125rem; font-weight: 600; color: #0284c7;">{{ __('Canteen') }}</div>
        <div style="font-size: 1.5rem; font-weight: 700; margin-top: 0.375rem;">
            ₱{{ number_format($credit->canteenOutstanding(), 2) }}
        </div>
        <button
            type="button"
            wire:click="mountAction('viewCanteenCreditDetails')"
            style="margin-top: 0.75rem; padding: 0.375rem 0.75rem; font-size: 0.8125rem; font-weight: 600; color: #0284c7; background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.25); border-radius: 0.5rem; cursor: pointer;"
        >
            {{ __('View details') }}
        </button>
    </div>
</div>
