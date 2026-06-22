<?php

namespace App\Filament\User\Pages;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class UserDashboard extends BaseDashboard
{
    #[\Override]
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    #[\Override]
    public function getWidgets(): array
    {
        return Filament::getWidgets();
    }

    #[\Override]
    public function content(Schema $schema): Schema
    {
        /** @var User $user */
        $user = auth()->user();

        return $schema
            ->components([
                ...(method_exists($this, 'getFiltersForm') ? [$this->getFiltersFormContentComponent()] : []),
                TextEntry::make('dashboard_welcome')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.dashboard-welcome', [
                            'user' => $user,
                        ])->render()
                    ))
                    ->columnSpanFull(),
                $this->getWidgetsContentComponent(),
            ]);
    }
}
