<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use App\Support\PrintMemberQrCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintMemberQrCodeController extends Controller
{
    public function member(Request $request, Member $member): View
    {
        $authUser = $request->user('web') ?? $request->user('member');

        abort_unless(
            ($authUser instanceof User && ($authUser->isAdmin() || $authUser->isCashier()))
                || ($authUser instanceof Member && $authUser->id === $member->id),
            403,
        );

        return view('filament.members.print-qr-code', PrintMemberQrCode::viewData(
            $member,
            autoPrint: $request->boolean('auto'),
        ));
    }
}
