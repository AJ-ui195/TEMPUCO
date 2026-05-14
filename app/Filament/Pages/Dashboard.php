<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\WelcomeWidget;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Filament\Widgets\WidgetConfiguration;

class Dashboard extends BaseDashboard
{
    protected static bool $isDiscovered = false;

    #[\Override]
    public function getWidgets(): array
    {
        return array_values(array_filter(
            Filament::getWidgets(),
            function (string | WidgetConfiguration $widget): bool {
                return $this->normalizeWidgetClass($widget) !== WelcomeWidget::class;
            }
        ));
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...(method_exists($this, 'getFiltersForm') ? [$this->getFiltersFormContentComponent()] : []),
                Livewire::make(WelcomeWidget::class, fn (): array => [
                    ...$this->getWidgetData(),
                    ...WelcomeWidget::getDefaultProperties(),
                ])
                    ->key('dashboard-welcome')
                    ->liberatedFromContainerGrid(),
                $this->getWidgetsContentComponent(),
            ]);
    }
}
