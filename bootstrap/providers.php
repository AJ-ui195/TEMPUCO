<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\PosCanteenPanelProvider;
use App\Providers\Filament\PosPanelProvider;
use App\Providers\Filament\UserPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    UserPanelProvider::class,
    PosPanelProvider::class,
    PosCanteenPanelProvider::class,
];
