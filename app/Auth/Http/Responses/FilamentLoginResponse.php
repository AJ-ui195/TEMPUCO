<?php

namespace App\Auth\Http\Responses;

use App\Support\RoleDashboard;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as Responsable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class FilamentLoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = RoleDashboard::currentUser();

        if (is_object($user) && ($user->must_change_password ?? false)) {
            $changePasswordUrl = RoleDashboard::changePasswordUrl($user);

            if (filled($changePasswordUrl)) {
                return redirect()->to($changePasswordUrl);
            }
        }

        $homeUrl = RoleDashboard::url($user);

        if (! $user instanceof FilamentUser || blank($homeUrl)) {
            return redirect()->to(RoleDashboard::loginUrl());
        }

        $intended = session()->pull('url.intended');

        if (is_string($intended) && $this->intendedBelongsToPanel($intended, $homeUrl)) {
            return redirect()->to($intended);
        }

        return redirect()->to($homeUrl);
    }

    protected function intendedBelongsToPanel(string $intended, string $homeUrl): bool
    {
        $homePath = parse_url($homeUrl, PHP_URL_PATH) ?? '';
        $intendedPath = parse_url($intended, PHP_URL_PATH) ?? '';

        $homePath = rtrim($homePath, '/');

        if ($homePath === '' || $intendedPath === '') {
            return false;
        }

        return $intendedPath === $homePath || str_starts_with($intendedPath, $homePath.'/');
    }
}
