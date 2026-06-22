@php
    $logo = filament()->getBrandLogo();
    $logoHeight = filament()->getBrandLogoHeight() ?? '3.5rem';
    $homeUrl = filament()->getHomeUrl();
@endphp

@if (filled($logo))
    <div class="fi-sidebar-brand-logo mb-6 w-full">
        @if ($homeUrl)
            <a {{ \Filament\Support\generate_href_html($homeUrl) }} class="fi-sidebar-brand-logo-link block">
                <img
                    src="{{ $logo }}"
                    alt="{{ __('filament-panels::layout.logo.alt', ['name' => filament()->getBrandName()]) }}"
                    class="fi-sidebar-brand-logo-img block object-contain"
                    style="height: {{ $logoHeight }}"
                />
            </a>
        @else
            <img
                src="{{ $logo }}"
                alt="{{ __('filament-panels::layout.logo.alt', ['name' => filament()->getBrandName()]) }}"
                class="fi-sidebar-brand-logo-img block object-contain"
                style="height: {{ $logoHeight }}"
            />
        @endif
    </div>
@endif

<style>
    /* Single sidebar logo: custom hook only (Filament also renders brandLogo in the header). */
    .fi-sidebar-brand-logo {
        display: flex !important;
        justify-content: center !important;
        padding-inline: 0;
        width: 100%;
    }

    .fi-sidebar-brand-logo-link,
    .fi-sidebar-brand-logo-img {
        margin-inline: auto !important;
    }

    .fi-body-has-topbar .fi-sidebar-header-ctn {
        display: none !important;
    }

    .fi-sidebar-brand-logo-img {
        filter: drop-shadow(0 0 3px rgba(255, 255, 255, 0.95))
            drop-shadow(0 0 8px rgba(255, 255, 255, 0.7))
            drop-shadow(0 0 14px rgba(147, 197, 253, 0.55));
    }
</style>
