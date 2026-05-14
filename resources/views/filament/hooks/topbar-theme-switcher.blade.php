@if (filament()->auth()->check() && filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
    <div
        class="fi-topbar-theme-switcher flex shrink-0 items-center"
        x-data="{ close() {} }"
    >
        <x-filament-panels::theme-switcher />
    </div>
@endif
