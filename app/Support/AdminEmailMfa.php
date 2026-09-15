<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\VerifyAdminEmailAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;

final class AdminEmailMfa
{
    public static function provider(): EmailAuthentication
    {
        return EmailAuthentication::make()
            ->codeNotification(VerifyAdminEmailAuthentication::class)
            ->codeExpiryMinutes(10);
    }

    public static function remember(User $user): void
    {
        if (! $user->isAdmin()) {
            return;
        }

        $user->forceFill([
            'email_mfa_verified_until' => now()->addDay(),
        ])->save();
    }

    public static function isRemembered(User $user): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        return $user->email_mfa_verified_until?->isFuture() ?? false;
    }
}
