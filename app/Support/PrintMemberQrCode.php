<?php

namespace App\Support;

use App\Models\User;

final class PrintMemberQrCode
{
    public static function printUrl(User $user, bool $autoPrint = true): string
    {
        return route('members.print-qr', [
            'user' => $user,
            'auto' => $autoPrint ? 1 : 0,
        ]);
    }

    /**
     * @return array{user: User, qrSvg: string, autoPrint: bool}
     */
    public static function viewData(User $user, bool $autoPrint = false): array
    {
        return [
            'user' => $user,
            'qrSvg' => MemberQrCode::printSvgMarkupFor($user),
            'autoPrint' => $autoPrint,
        ];
    }
}
