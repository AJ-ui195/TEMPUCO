<?php

namespace App\Filament\User\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserDashboard extends BaseDashboard
{
    #[\Override]
    public function getWidgets(): array
    {
        return Filament::getWidgets();
    }

    #[\Override]
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...(method_exists($this, 'getFiltersForm') ? [$this->getFiltersFormContentComponent()] : []),
                Section::make(__('Welcome'))
                    ->description(__('You are signed in as a member. This is your personal area.')),
                $this->getWidgetsContentComponent(),
            ]);
    }
}
