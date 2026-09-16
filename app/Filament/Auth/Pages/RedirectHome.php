<?php

namespace App\Filament\Auth\Pages;

use App\Support\RoleDashboard;
use Filament\Pages\Dashboard as BaseDashboard;

class RedirectHome extends BaseDashboard
{
    protected static bool $isDiscovered = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $url = RoleDashboard::url();

        $this->redirect($url ?: RoleDashboard::loginUrl());
    }
}
