<div class="mp-qr-card">
    <p class="mp-stat-label" style="text-transform: uppercase; letter-spacing: 0.06em;">{{ __('TEMPUCO Member') }}</p>
    <h2 class="mp-qr-name" style="margin-top: 0.5rem;">{{ $user->name }}</h2>

    @if (filled($user->email))
        <p class="mp-qr-detail">{{ $user->email }}</p>
    @endif

    @if (filled($user->cellphone))
        <p class="mp-qr-detail">{{ $user->cellphone }}</p>
    @endif

    <div class="mp-qr-frame">
        <img
            src="{{ $qrCodeDataUri }}"
            alt="{{ __('QR code for :name', ['name' => $user->name]) }}"
            width="160"
            height="160"
        >
    </div>

    <span class="mp-qr-badge">{{ __('Member ID') }}: #{{ $user->id }}</span>

    <p class="mp-qr-detail" style="margin-top: 1rem; font-size: 0.8125rem; line-height: 1.5;">
        {{ __('Present this code when making purchases or when requested by cooperative staff.') }}
    </p>
</div>
