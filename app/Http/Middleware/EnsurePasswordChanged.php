<?php

namespace App\Http\Middleware;

use App\Filament\Auth\Pages\ChangePassword;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user || ! ($user->must_change_password ?? false)) {
            return $next($request);
        }

        if ($this->isAllowedWhilePasswordChangeRequired($request)) {
            return $next($request);
        }

        return redirect()->to(ChangePassword::getUrl());
    }

    private function isAllowedWhilePasswordChangeRequired(Request $request): bool
    {
        $path = trim($request->path(), '/');

        if (str_contains($path, 'change-password') || str_contains($path, 'logout')) {
            return true;
        }

        if ($request->routeIs('filament.*.auth.logout') || $request->routeIs('livewire.*')) {
            return true;
        }

        return false;
    }
}
