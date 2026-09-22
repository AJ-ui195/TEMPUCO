<x-filament-panels::page>
    <div class="fi-pos-ui" style="max-width: 28rem;">
        <div class="pos-panel" style="padding: 0; overflow: hidden;">
            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.85rem 1.15rem; background: rgb(99 102 241); color: #fff;">
                <span aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="11" width="14" height="10" rx="2" />
                        <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                    </svg>
                </span>
                <h2 style="margin: 0; font-size: 1.05rem; font-weight: 700;">{{ __('Change PIN') }}</h2>
            </div>

            <form wire:submit="updatePin" style="padding: 1.15rem 1.25rem 1.35rem;">
                <p class="pos-muted" style="margin: 0 0 1.1rem; font-size: 0.875rem; line-height: 1.45;">
                    {{ __('This PIN is required to edit member details or delete a member.') }}
                </p>

                <label for="current-pin" style="display: block; font-size: 0.875rem; font-weight: 700; margin-bottom: 0.375rem;">
                    {{ __('Current PIN') }} <span style="color: rgb(220 38 38);">*</span>
                </label>
                <input
                    id="current-pin"
                    type="password"
                    wire:model="currentPin"
                    autocomplete="current-password"
                    class="pos-input"
                    style="margin-bottom: 0.35rem;"
                />
                @error('currentPin')
                    <p style="margin: 0 0 0.85rem; font-size: 0.75rem; font-weight: 600; color: rgb(185 28 28);">{{ $message }}</p>
                @else
                    <div style="height: 0.85rem;"></div>
                @enderror

                <label for="new-pin" style="display: block; font-size: 0.875rem; font-weight: 700; margin-bottom: 0.375rem;">
                    {{ __('New PIN') }} <span style="color: rgb(220 38 38);">*</span>
                </label>
                <input
                    id="new-pin"
                    type="password"
                    wire:model="newPin"
                    autocomplete="new-password"
                    class="pos-input"
                    style="margin-bottom: 0.35rem;"
                />
                <p class="pos-muted" style="margin: 0 0 0.85rem; font-size: 0.75rem;">
                    {{ __('At least :min characters.', ['min' => \App\Support\MemberEditPin::MIN_LENGTH]) }}
                </p>
                @error('newPin')
                    <p style="margin: -0.5rem 0 0.85rem; font-size: 0.75rem; font-weight: 600; color: rgb(185 28 28);">{{ $message }}</p>
                @enderror

                <label for="confirm-pin" style="display: block; font-size: 0.875rem; font-weight: 700; margin-bottom: 0.375rem;">
                    {{ __('Confirm New PIN') }} <span style="color: rgb(220 38 38);">*</span>
                </label>
                <input
                    id="confirm-pin"
                    type="password"
                    wire:model="newPinConfirmation"
                    autocomplete="new-password"
                    class="pos-input"
                    style="margin-bottom: 0.35rem;"
                />
                @error('newPinConfirmation')
                    <p style="margin: 0 0 1rem; font-size: 0.75rem; font-weight: 600; color: rgb(185 28 28);">{{ $message }}</p>
                @else
                    <div style="height: 0.65rem;"></div>
                @enderror

                <button type="submit" class="pos-btn-primary" style="width: auto; margin-top: 0.5rem;">
                    {{ __('Update PIN') }}
                </button>
            </form>
        </div>
    </div>

    @include('filament.cashier.partials.pos-ui-styles')
</x-filament-panels::page>
