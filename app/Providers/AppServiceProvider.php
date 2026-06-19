<?php

namespace App\Providers;

use App\Auth\Http\Responses\FilamentLoginResponse;
use App\Models\PosBranchInventory;
use App\Models\PosInventoryItem;
use App\Observers\PosBranchInventoryObserver;
use App\Observers\PosInventoryItemObserver;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
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
        PosInventoryItem::observe(PosInventoryItemObserver::class);
        PosBranchInventory::observe(PosBranchInventoryObserver::class);
    }
}
