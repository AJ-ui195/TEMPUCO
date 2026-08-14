<div class="mp-qr-card">
    <p class="mp-stat-label" style="text-transform: uppercase; letter-spacing: 0.06em;">{{ __('TEMPUCO Member') }}</p>
    <h2 class="mp-qr-name" style="margin-top: 0.5rem;">{{ $user->name }}</h2>

    <div class="mp-qr-frame">
        <span class="mp-qr-corner mp-qr-corner--tl" aria-hidden="true"></span>
        <span class="mp-qr-corner mp-qr-corner--tr" aria-hidden="true"></span>
        <span class="mp-qr-corner mp-qr-corner--bl" aria-hidden="true"></span>
        <span class="mp-qr-corner mp-qr-corner--br" aria-hidden="true"></span>
        <div class="mp-qr-pad">
            <img
                src="{{ $qrCodeDataUri }}"
                alt="{{ __('QR code for :name', ['name' => $user->name]) }}"
                width="160"
                height="160"
            >
        </div>
    </div>

    <p class="mp-qr-scan-label">{{ __('Scan to identify') }}</p>

    <p class="mp-qr-detail" style="margin-top: 1rem; font-size: 0.8125rem; line-height: 1.5;">
        {{ __('Present this code when making purchases or when requested by cooperative staff.') }}
    </p>
</div>
