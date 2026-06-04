<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\Login;
use App\Providers\Filament\Concerns\RegistersPortalUi;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PosPanelProvider extends PanelProvider
{
    use RegistersPortalUi;

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('pos')
            ->path('pos')
            ->login(Login::class)
            ->brandName(__('POS'))
            ->brandLogo(asset('images/DICNHSLOGO1.png'))
            ->brandLogoHeight('6.5rem')
            ->colors([
                'primary' => Color::Sky,
            ])
            ->globalSearch(false)
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->userMenu(false)
            ->discoverPages(in: app_path('Filament/Cashier/Pages'), for: 'App\Filament\Cashier\Pages')
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

        return $this->registerPortalUi($panel);
    }
}
