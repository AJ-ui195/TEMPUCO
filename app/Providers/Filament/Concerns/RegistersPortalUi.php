<?php

namespace App\Providers\Filament\Concerns;

use Filament\Panel;
use Filament\View\PanelsRenderHook;

trait RegistersPortalUi
{
    protected function registerPortalUi(Panel $panel): Panel
    {
        return $panel
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.hooks.hide-topbar-logo-on-desktop')->render(),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.hooks.auth-login-background')->render(),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.hooks.sidebar-dark-theme')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): string => view('filament.hooks.topbar-brand-name')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): string => view('filament.hooks.sidebar-brand-logo')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => view('filament.hooks.topbar-theme-switcher')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => view('filament.hooks.sidebar-logout')->render(),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => view('filament.hooks.auth-login-portal-links', [
                    'currentPanel' => $panel->getId(),
                ])->render(),
            );
    }
}
