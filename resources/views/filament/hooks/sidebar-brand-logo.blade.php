@php
    $logo = filament()->getBrandLogo();
    $logoHeight = filament()->getBrandLogoHeight() ?? '3.5rem';
    $homeUrl = filament()->getHomeUrl();
@endphp

@if (filled($logo))
    <div class="fi-sidebar-brand-logo mb-6 hidden w-full lg:flex lg:justify-center">
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
    .fi-sidebar-brand-logo {
        justify-content: center !important;
        padding-inline: 0;
    }

    .fi-sidebar-brand-logo-link,
    .fi-sidebar-brand-logo-img {
        margin-inline: auto !important;
    }

    .fi-sidebar-header-logo-ctn {
        display: flex !important;
        flex: none;
        width: 100% !important;
        justify-content: center !important;
        align-items: center !important;
        text-align: center;
    }

    .fi-sidebar-header-logo-ctn > a {
        display: block;
        margin-inline: auto !important;
    }

    .fi-sidebar-header-logo-ctn .fi-logo,
    .fi-sidebar-header-logo-ctn img.fi-logo {
        margin-inline: auto !important;
        margin-inline-start: auto !important;
    }

    .fi-sidebar-brand-logo-img,
    .fi-sidebar-header-logo-ctn img.fi-logo {
        filter: drop-shadow(0 0 3px rgba(255, 255, 255, 0.95))
            drop-shadow(0 0 8px rgba(255, 255, 255, 0.7))
            drop-shadow(0 0 14px rgba(147, 197, 253, 0.55));
    }
</style>
