<style>
    .mp-qr-modal {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.75rem;
        padding: 0.5rem 0 0.25rem;
    }

    .mp-qr-modal-kicker {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: rgb(100 116 139);
    }

    .mp-qr-modal-name {
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .mp-qr-modal .mp-qr-frame {
        position: relative;
        display: inline-flex;
        padding: 1.15rem;
        margin-top: 0.25rem;
        border-radius: 1.15rem;
        background:
            linear-gradient(#fff, #fff) padding-box,
            linear-gradient(180deg, #38bdf8, #0284c7) border-box;
        border: 2px solid transparent;
        box-shadow: 0 10px 24px rgba(14, 116, 144, 0.12);
    }

    .mp-qr-modal .mp-qr-pad {
        display: flex;
        padding: 0.5rem;
        border-radius: 0.65rem;
        border: 1.5px dashed rgba(14, 165, 233, 0.35);
        background: #fff;
    }

    .mp-qr-modal .mp-qr-corner {
        position: absolute;
        width: 1.35rem;
        height: 1.35rem;
        pointer-events: none;
        border: 3px solid #0284c7;
    }

    .mp-qr-modal .mp-qr-corner--tl {
        top: 0.45rem;
        left: 0.45rem;
        border-right: 0;
        border-bottom: 0;
        border-radius: 0.35rem 0 0 0;
    }

    .mp-qr-modal .mp-qr-corner--tr {
        top: 0.45rem;
        right: 0.45rem;
        border-left: 0;
        border-bottom: 0;
        border-radius: 0 0.35rem 0 0;
    }

    .mp-qr-modal .mp-qr-corner--bl {
        bottom: 0.45rem;
        left: 0.45rem;
        border-right: 0;
        border-top: 0;
        border-radius: 0 0 0 0.35rem;
    }

    .mp-qr-modal .mp-qr-corner--br {
        bottom: 0.45rem;
        right: 0.45rem;
        border-left: 0;
        border-top: 0;
        border-radius: 0 0 0.35rem 0;
    }

    .mp-qr-modal .mp-qr-frame img {
        display: block;
        width: 200px;
        height: 200px;
    }

    .mp-qr-modal .mp-qr-scan-label {
        margin: 0;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #0284c7;
    }

    .mp-qr-modal-hint {
        margin: 0;
        max-width: 22rem;
        font-size: 0.8125rem;
        line-height: 1.5;
        color: rgb(100 116 139);
    }
</style>

<div class="mp-qr-modal">
    <div class="mp-qr-modal-kicker">
        {{ __('TEMPUCO Member') }}
    </div>

    <div class="mp-qr-modal-name">
        {{ $user->name }}
    </div>

    <div class="mp-qr-frame">
        <span class="mp-qr-corner mp-qr-corner--tl" aria-hidden="true"></span>
        <span class="mp-qr-corner mp-qr-corner--tr" aria-hidden="true"></span>
        <span class="mp-qr-corner mp-qr-corner--bl" aria-hidden="true"></span>
        <span class="mp-qr-corner mp-qr-corner--br" aria-hidden="true"></span>
        <div class="mp-qr-pad">
            <img
                src="{{ $qrCodeDataUri }}"
                alt="{{ __('QR code for :name', ['name' => $user->name]) }}"
                width="200"
                height="200"
            >
        </div>
    </div>

    <p class="mp-qr-scan-label">{{ __('Scan to identify') }}</p>

    <p class="mp-qr-modal-hint">
        {{ __('Scan this code at the POS when the member buys. Print a copy for their wallet or ID.') }}
    </p>
</div>
