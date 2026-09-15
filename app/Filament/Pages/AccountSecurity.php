<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class AccountSecurity extends Page
{
    protected static ?string $navigationLabel = 'Account security';

    protected static ?string $title = 'Account security';

    protected static ?string $slug = 'account-security';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 90;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public function getTitle(): string|Htmlable
    {
        return __('Account security');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Email sign-in code'))
                    ->description(__('A 6-digit code is sent to your email when you sign in. After you enter it, this device will not ask again for 1 day.'))
                    ->schema(
                        collect(Filament::getMultiFactorAuthenticationProviders())
                            ->map(fn (MultiFactorAuthenticationProvider $provider): Group => Group::make($provider->getManagementSchemaComponents())
                                ->statePath($provider->getId()))
                            ->all()
                    ),
            ]);
    }
}
