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
                Section::make(__('Authenticator app'))
                    ->description(__('Required for admin sign-in. Scan the QR code with an authenticator app and store your recovery codes.'))
                    ->schema(
                        collect(Filament::getMultiFactorAuthenticationProviders())
                            ->map(fn (MultiFactorAuthenticationProvider $provider): Group => Group::make($provider->getManagementSchemaComponents())
                                ->statePath($provider->getId()))
                            ->all()
                    ),
            ]);
    }
}
