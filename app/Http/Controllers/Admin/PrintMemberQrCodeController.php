<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PrintMemberQrCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintMemberQrCodeController extends Controller
{
    public function __invoke(Request $request, User $user): View
    {
        $authUser = $request->user();

        abort_unless(
            $authUser?->isAdmin() || $authUser?->id === $user->id,
            403,
        );

        return view('filament.members.print-qr-code', PrintMemberQrCode::viewData(
            $user,
            autoPrint: $request->boolean('auto'),
        ));
    }
}
