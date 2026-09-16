<?php

namespace App\Auth\Http\Responses;

use App\Support\RoleDashboard;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportRedirects\Redirector;

class FilamentLogoutResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        Auth::guard('web')->logout();
        Auth::guard('member')->logout();

        return redirect()->to(RoleDashboard::loginUrl());
    }
}
