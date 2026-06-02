<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MemberQrCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintMemberQrCodeController extends Controller
{
    public function __invoke(Request $request, User $user): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('filament.members.print-qr-code', [
            'user' => $user,
            'qrCodeDataUri' => MemberQrCode::printDataUriFor($user),
            'autoPrint' => $request->boolean('auto'),
        ]);
    }
}
