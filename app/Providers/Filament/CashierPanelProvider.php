<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\Login;
use App\Filament\CollectionCashier\Pages\CollectionPayment;
use App\Filament\CollectionCashier\Pages\Dashboard;
use App\Filament\CollectionCashier\Pages\RegularLoanRemittance;
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

class CashierPanelProvider extends PanelProvider
{
    use RegistersPortalUi;

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('cashier')
            ->path('cashier')
            ->spa()
            ->login(Login::class)
            ->brandName(__('Cashier'))
            ->brandLogo(asset('images/DICNHSLOGO1.png'))
            ->brandLogoHeight('6.5rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->globalSearch(false)
            ->databaseNotifications()
            ->userMenu(false)
            ->pages([
                Dashboard::class,
                CollectionPayment::class,
                RegularLoanRemittance::class,
            ])
            ->widgets([])
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
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => view('filament.hooks.login-panel-switch', [
                    'url' => url('/admin/login'),
                    'message' => __('Click here to login to Admin'),
                ])->render(),
            );
    }
}
