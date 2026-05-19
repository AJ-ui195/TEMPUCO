<?php

namespace App\Auth\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as Responsable;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class FilamentLoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        if (! $user instanceof FilamentUser) {
            return redirect()->intended(Filament::getUrl());
        }

        $intended = session()->pull('url.intended');
        $homeUrl = $this->resolveHomeUrl($user);

        if (is_string($intended) && $this->intendedBelongsToPanel($intended, $homeUrl)) {
            return redirect()->to($intended);
        }

        return redirect()->to($homeUrl);
    }

    protected function resolveHomeUrl(FilamentUser $user): string
    {
        if ($user->canAccessPanel(Filament::getPanel('admin'))) {
            return Filament::getPanel('admin')->getUrl();
        }

        if ($user->canAccessPanel(Filament::getPanel('user'))) {
            return Filament::getPanel('user')->getUrl();
        }

        return Filament::getUrl();
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
