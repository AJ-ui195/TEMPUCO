@php
    use App\Support\MemberPosCredit;

    $credit = new MemberPosCredit($user);
@endphp

<div style="display: flex; flex-direction: column; gap: 1.25rem;">
    <p style="margin: 0; font-size: 0.875rem; color: rgb(100 116 139);">
        {{ __('Amounts you still owe from grocery and canteen purchases charged to your account.') }}
    </p>

    <div style="display: grid; gap: 1rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); background: rgb(248 250 252);">
            <div>
                <div style="font-size: 0.8125rem; font-weight: 600; color: rgb(3 105 161);">{{ __('Grocery') }}</div>
                <div style="font-size: 1.375rem; font-weight: 700; margin-top: 0.25rem;">
                    ₱{{ number_format($credit->groceryOutstanding(), 2) }}
                </div>
            </div>
            <button
                type="button"
                wire:click="mountAction('viewGroceryCreditDetails')"
                style="flex-shrink: 0; padding: 0.5rem 0.875rem; font-size: 0.8125rem; font-weight: 600; color: #fff; background: #0284c7; border: none; border-radius: 0.5rem; cursor: pointer;"
            >
                {{ __('View details') }}
            </button>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); background: rgb(248 250 252);">
            <div>
                <div style="font-size: 0.8125rem; font-weight: 600; color: rgb(3 105 161);">{{ __('Canteen') }}</div>
                <div style="font-size: 1.375rem; font-weight: 700; margin-top: 0.25rem;">
                    ₱{{ number_format($credit->canteenOutstanding(), 2) }}
                </div>
            </div>
            <button
                type="button"
                wire:click="mountAction('viewCanteenCreditDetails')"
                style="flex-shrink: 0; padding: 0.5rem 0.875rem; font-size: 0.8125rem; font-weight: 600; color: #fff; background: #0284c7; border: none; border-radius: 0.5rem; cursor: pointer;"
            >
                {{ __('View details') }}
            </button>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; padding-top: 0.75rem; border-top: 1px solid rgb(226 232 240); font-size: 0.9375rem; font-weight: 600;">
        <span>{{ __('Total outstanding') }}</span>
        <span>₱{{ number_format($credit->totalOutstanding(), 2) }}</span>
    </div>
</div>
