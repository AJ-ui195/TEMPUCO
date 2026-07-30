<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class TopPoints extends Page
{
    protected static ?string $navigationLabel = 'Top Points';

    protected static ?string $title = 'Top Points';

    protected static ?string $slug = 'top-points';

    protected static ?int $navigationSort = 11;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected string $view = 'filament.pages.top-points';

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Top Points');
    }

    /**
     * @return Collection<int, User>|EloquentCollection<int, User>
     */
    public function getTopMembers(): Collection|EloquentCollection
    {
        return User::query()
            ->members()
            ->where('points', '>', 0)
            ->orderByDesc('points')
            ->orderedByName()
            ->limit(10)
            ->get();
    }
}
