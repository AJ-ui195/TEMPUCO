<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\Login;
use App\Filament\Canteen\Pages\CanteenInventoryPage;
use App\Filament\Canteen\Pages\PosCanteenPage;
use App\Providers\Filament\Concerns\RegistersPortalUi;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PosCanteenPanelProvider extends PanelProvider
{
    use RegistersPortalUi;

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('pos-canteen')
            ->path('pos/canteen')
            ->spa()
            ->login(Login::class)
            ->brandName(__('Canteen POS'))
            ->brandLogo(asset('images/DICNHSLOGO1.png'))
            ->brandLogoHeight('6.5rem')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->globalSearch(false)
            ->databaseNotifications()
            ->userMenu(false)
            ->pages([
                PosCanteenPage::class,
                CanteenInventoryPage::class,
            ])
            ->discoverResources(in: app_path('Filament/Canteen/Resources'), for: 'App\Filament\Canteen\Resources')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        return $this->registerPortalUi($panel)
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.hooks.canteen-pos-ui')->render(),
            );
    }
}
