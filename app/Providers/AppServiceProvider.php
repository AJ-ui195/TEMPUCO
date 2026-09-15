<?php

namespace App\Providers;

use App\Auth\Http\Responses\FilamentLoginResponse;
use App\Models\AuditLog;
use App\Models\CharacterLoan;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\PosBranchInventory;
use App\Models\PosCanteenInventoryItem;
use App\Models\PosInventoryItem;
use App\Models\QuickLoan;
use App\Models\RegularLoan;
use App\Models\User;
use App\Observers\PosBranchInventoryObserver;
use App\Observers\PosCanteenInventoryItemObserver;
use App\Observers\PosInventoryItemObserver;
use App\Policies\AuditLogPolicy;
use App\Policies\LoanPaymentPolicy;
use App\Policies\LoanPolicy;
use App\Policies\MemberPolicy;
use App\Policies\UserPolicy;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, FilamentLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(RegularLoan::class, LoanPolicy::class);
        Gate::policy(QuickLoan::class, LoanPolicy::class);
        Gate::policy(CharacterLoan::class, LoanPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(LoanPayment::class, LoanPaymentPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        PosInventoryItem::observe(PosInventoryItemObserver::class);
        PosCanteenInventoryItem::observe(PosCanteenInventoryItemObserver::class);
        PosBranchInventory::observe(PosBranchInventoryObserver::class);
    }
}
