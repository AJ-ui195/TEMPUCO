<div
    x-data="{
        deferredPrompt: null,
        canInstall: false,
        isInstalled: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
        isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1),
        isAndroid: /Android/i.test(navigator.userAgent),
        isMacSafari: (() => {
            const ua = navigator.userAgent;
            const isAppleTouchMac = navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;

            return ! isAppleTouchMac
                && /Macintosh|Mac OS X/.test(ua)
                && /Safari/.test(ua)
                && ! /Chrome|Chromium|Edg|OPR|Firefox/.test(ua);
        })(),
        init() {
            window.addEventListener('beforeinstallprompt', (event) => {
                event.preventDefault();
                this.deferredPrompt = event;
                this.canInstall = true;
            });

            window.addEventListener('appinstalled', () => {
                this.deferredPrompt = null;
                this.canInstall = false;
                this.isInstalled = true;
            });
        },
        async install() {
            if (! this.deferredPrompt) {
                return;
            }

            this.deferredPrompt.prompt();
            await this.deferredPrompt.userChoice;
            this.deferredPrompt = null;
            this.canInstall = false;
        },
    }"
    x-show="! isInstalled"
    class="mp-pwa-install"
>
    <p class="mp-pwa-install__title" x-text="isMacSafari ? @js(__('Install Members Portal on your Mac')) : @js(__('Install Members Portal on your phone'))">
        {{ __('Install Members Portal on your phone') }}
    </p>

    <button
        type="button"
        x-show="canInstall"
        x-cloak
        x-on:click="install()"
        class="mp-pwa-install__btn"
    >
        {{ __('Install app') }}
    </button>

    <div class="mp-pwa-install__grid">
        {{-- macOS Safari (desktop) --}}
        <div
            class="mp-pwa-install__card"
            x-show="isMacSafari"
            x-cloak
            x-bind:class="{ 'mp-pwa-install__card--active': isMacSafari }"
        >
            <p class="mp-pwa-install__card-title">
                <span class="mp-pwa-install__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                        <path d="M12 3c-1.1 0-2 .9-2 2v6H8.83l4.17 4.17L17.17 11H14V5c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2 .9-2 2v4c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-4c0-1.1-.9-2-2-2H6z"/>
                    </svg>
                </span>
                {{ __('Mac (Safari)') }}
            </p>
            <ol class="mp-pwa-install__steps">
                <li>
                    {{ __('Click') }}
                    <strong>{{ __('Share') }}</strong>
                    <span class="mp-pwa-install__share-hint" aria-hidden="true">&#x2B06;&#xFE0E;</span>
                    {{ __('in the top-right Safari toolbar') }}
                </li>
                <li>
                    {{ __('Choose') }}
                    <strong>{{ __('Add to Dock') }}</strong>
                </li>
            </ol>
            <p class="mp-pwa-install__note">
                {{ __('Or use the menu bar: File → Add to Dock. Safari does not show an Install button in the address bar.') }}
            </p>
        </div>

        {{-- iPhone / iPad Safari --}}
        <div
            class="mp-pwa-install__card"
            x-show="isIOS && ! isMacSafari"
            x-cloak
            x-bind:class="{ 'mp-pwa-install__card--active': isIOS }"
        >
            <p class="mp-pwa-install__card-title">
                <span class="mp-pwa-install__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                        <path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/>
                    </svg>
                </span>
                {{ __('iPhone / iPad (Safari)') }}
            </p>
            <ol class="mp-pwa-install__steps">
                <li>
                    {{ __('Tap') }}
                    <strong>{{ __('Share') }}</strong>
                    <span class="mp-pwa-install__share-hint" aria-hidden="true">&#x2B06;&#xFE0E;</span>
                    {{ __('at the bottom of Safari') }}
                </li>
                <li>
                    {{ __('Tap') }}
                    <strong>{{ __('Add to Home Screen') }}</strong>
                </li>
            </ol>
            <p class="mp-pwa-install__note">
                {{ __('Safari does not show an Install button — use Share instead.') }}
            </p>
        </div>

        {{-- Android Chrome --}}
        <div
            class="mp-pwa-install__card"
            x-show="isAndroid"
            x-cloak
            x-bind:class="{ 'mp-pwa-install__card--active': isAndroid }"
        >
            <p class="mp-pwa-install__card-title">
                <span class="mp-pwa-install__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                        <path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/>
                    </svg>
                </span>
                {{ __('Android (Chrome)') }}
            </p>
            <ol class="mp-pwa-install__steps">
                <li>
                    {{ __('Open the') }}
                    <strong>{{ __('menu') }}</strong>
                    {{ __('(three dots)') }}
                </li>
                <li>
                    <strong>{{ __('Install app') }}</strong>
                    {{ __('or') }}
                    <strong>{{ __('Add to Home screen') }}</strong>
                </li>
            </ol>
        </div>

        {{-- Other browsers / desktop fallback --}}
        <div
            class="mp-pwa-install__card"
            x-show="! isMacSafari && ! isIOS && ! isAndroid"
            x-cloak
        >
            <p class="mp-pwa-install__card-title">
                {{ __('Install on mobile') }}
            </p>
            <p class="mp-pwa-install__fallback">
                {{ __('Open this page on your phone in Safari (iPhone) or Chrome (Android), then follow the install steps shown there.') }}
            </p>
        </div>
    </div>
</div>

<style>
    .mp-pwa-install {
        margin-top: 1.5rem;
        padding: 1rem 1.125rem;
        border-radius: 0.875rem;
        border: 1px solid rgba(255, 255, 255, 0.35);
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        font-size: 0.875rem;
        line-height: 1.5;
        color: #f8fafc;
        text-align: left;
    }

    html.dark .mp-pwa-install {
        border-color: rgba(255, 255, 255, 0.2);
        background: rgba(15, 23, 42, 0.45);
    }

    .mp-pwa-install__title {
        margin: 0;
        text-align: center;
        font-size: 0.9375rem;
        font-weight: 700;
        color: #fff;
    }

    .mp-pwa-install__btn {
        display: block;
        width: 100%;
        margin-top: 0.875rem;
        padding: 0.625rem 1rem;
        border: none;
        border-radius: 0.625rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #fff;
        cursor: pointer;
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
        box-shadow: 0 4px 14px rgba(14, 165, 233, 0.4);
    }

    .mp-pwa-install__btn:hover {
        background: linear-gradient(135deg, #38bdf8, #0ea5e9);
    }

    .mp-pwa-install__grid {
        display: grid;
        gap: 0.75rem;
        margin-top: 0.875rem;
    }

    .mp-pwa-install__card {
        padding: 0.75rem 0.875rem;
        border-radius: 0.625rem;
        border: 1px solid rgba(255, 255, 255, 0.25);
        background: rgba(0, 0, 0, 0.2);
    }

    .mp-pwa-install__card--active {
        border-color: rgba(56, 189, 248, 0.75);
        box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.35);
        background: rgba(14, 165, 233, 0.15);
    }

    .mp-pwa-install__card-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        font-size: 0.8125rem;
        font-weight: 700;
        color: #e0f2fe;
    }

    .mp-pwa-install__icon {
        display: inline-flex;
        flex-shrink: 0;
        color: #38bdf8;
    }

    .mp-pwa-install__steps {
        margin: 0.625rem 0 0;
        padding-left: 1.25rem;
        color: rgba(248, 250, 252, 0.9);
    }

    .mp-pwa-install__steps li + li {
        margin-top: 0.375rem;
    }

    .mp-pwa-install__steps strong {
        color: #fff;
        font-weight: 700;
    }

    .mp-pwa-install__share-hint {
        display: inline-block;
        margin: 0 0.125rem;
        font-size: 1rem;
        line-height: 1;
        vertical-align: middle;
    }

    .mp-pwa-install__note,
    .mp-pwa-install__fallback {
        margin: 0.625rem 0 0;
        padding: 0.5rem 0.625rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        line-height: 1.45;
        color: #bae6fd;
        background: rgba(14, 165, 233, 0.2);
        border: 1px solid rgba(56, 189, 248, 0.3);
    }

    .mp-pwa-install__fallback {
        margin-top: 0;
    }

    .mp-pwa-install [x-cloak] {
        display: none !important;
    }
</style>
