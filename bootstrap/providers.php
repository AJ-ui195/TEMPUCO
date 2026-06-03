<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\InventoryPanelProvider;
use App\Providers\Filament\PosPanelProvider;
use App\Providers\Filament\UserPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    UserPanelProvider::class,
    PosPanelProvider::class,
    InventoryPanelProvider::class,
];
