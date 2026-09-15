<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Filament\Auth\Pages\ChangePassword;
use App\Models\Member;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

final class RoleDashboard
{
    public static function loginUrl(): string
    {
        return Filament::getPanel('auth')->getLoginUrl() ?? url('/login');
    }

    public static function currentUser(): User|Member|null
    {
        $staff = Auth::guard('web')->user();

        if ($staff instanceof User) {
            return $staff;
        }

        $member = Auth::guard('member')->user();

        return $member instanceof Member ? $member : null;
    }

    public static function panelId(?Authenticatable $account = null): ?string
    {
        $account ??= self::currentUser();

        if ($account instanceof Member) {
            return 'user';
        }

        if (! $account instanceof User) {
            return null;
        }

        return match ($account->role) {
            UserRole::Admin => 'admin',
            UserRole::Cashier, UserRole::Inventory => 'pos',
            UserRole::CanteenCashier => 'pos-canteen',
            UserRole::CollectionCashier => 'cashier',
            default => null,
        };
    }

    public static function url(?Authenticatable $account = null): ?string
    {
        $panelId = self::panelId($account);

        if ($panelId === null) {
            return null;
        }

        return Filament::getPanel($panelId)->getUrl();
    }

    public static function changePasswordUrl(?Authenticatable $account = null): ?string
    {
        $panelId = self::panelId($account);

        if ($panelId === null) {
            return null;
        }

        return ChangePassword::getUrl(panel: $panelId);
    }
}
