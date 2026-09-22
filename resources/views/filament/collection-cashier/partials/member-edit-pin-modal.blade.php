@if ($showPinModal)
    <div class="pos-credit-modal" wire:key="member-edit-pin">
        <div class="pos-credit-modal__backdrop" wire:click="closePinModal"></div>
        <div class="pos-credit-modal__dialog col-pay-saved col-pay-pin">
            <h3>{{ __('Enter PIN') }}</h3>
            <p>{{ __('PIN is required for :action.', ['action' => $this->pinActionLabel()]) }}</p>
            <form wire:submit.prevent="confirmPinAction">
                <input
                    type="password"
                    wire:model="actionPin"
                    autocomplete="off"
                    class="pos-input"
                    placeholder="{{ __('PIN') }}"
                />
                @if ($pinError)
                    <p class="col-pay-pin-error">{{ $pinError }}</p>
                @endif
                <div class="col-pay-pin-actions">
                    <button type="button" wire:click="closePinModal">{{ __('Cancel') }}</button>
                    <button type="submit">{{ __('Continue') }}</button>
                </div>
            </form>
        </div>
    </div>
@endif

<style>
    .col-pay-pin { text-align: center; }
    .col-pay-pin form { margin: 0; }
    .col-pay-pin .pos-input {
        width: 100%;
        text-align: center;
        margin: 0 0 0.85rem;
    }
    .col-pay-pin-error {
        margin: -0.45rem 0 0.75rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: rgb(185 28 28);
        text-align: center;
    }
    .col-pay-pin-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    .col-pay-saved.col-pay-pin .col-pay-pin-actions button {
        width: 100%;
        box-sizing: border-box;
        border: 0;
        border-radius: 0.55rem;
        padding: 0.7rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 700;
        cursor: pointer;
        text-align: center;
        margin: 0;
    }
    .col-pay-saved.col-pay-pin .col-pay-pin-actions button[type="button"] {
        background: #e2e8f0;
        color: #0f172a;
    }
    .col-pay-saved.col-pay-pin .col-pay-pin-actions button[type="submit"] {
        background: #2563eb;
        color: #fff;
    }
    .col-pay-saved.col-pay-pin .col-pay-pin-actions button[type="submit"]:hover { background: #1d4ed8; }
    .dark .col-pay-saved.col-pay-pin .col-pay-pin-actions button[type="button"] {
        background: rgb(51 65 85);
        color: #fff;
    }
</style>
