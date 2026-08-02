<div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 0.75rem; padding: 0.5rem 0 0.25rem;">
    <div style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(100 116 139);">
        {{ __('TEMPUCO Member') }}
    </div>

    <div style="font-size: 1.25rem; font-weight: 700; line-height: 1.3;">
        {{ $user->name }}
    </div>

    @if (filled($user->email))
        <div style="font-size: 0.875rem; color: rgb(100 116 139);">{{ $user->email }}</div>
    @endif

    @if (filled($user->cellphone))
        <div style="font-size: 0.875rem; color: rgb(100 116 139);">{{ $user->cellphone }}</div>
    @endif

    <div style="margin-top: 0.5rem; padding: 0.75rem; background: #fff; border-radius: 0.75rem; border: 1px solid rgb(226 232 240);">
        <img
            src="{{ $qrCodeDataUri }}"
            alt="{{ __('QR code for :name', ['name' => $user->name]) }}"
            width="200"
            height="200"
            style="display: block; width: 200px; height: 200px;"
        >
    </div>

    <div style="font-size: 0.8125rem; font-weight: 600; color: rgb(100 116 139);">
        {{ __('Member ID') }}: #{{ $user->id }}
    </div>

    <p style="margin: 0; max-width: 22rem; font-size: 0.8125rem; line-height: 1.5; color: rgb(100 116 139);">
        {{ __('Scan this code at the POS when the member buys. Print a copy for their wallet or ID.') }}
    </p>
</div>
