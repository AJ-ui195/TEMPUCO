<?php

namespace App\Support;

use App\Models\Member;

final class PrintMemberQrCode
{
    public static function printUrl(Member $member, bool $autoPrint = true): string
    {
        return route('members.print-qr', [
            'member' => $member,
            'auto' => $autoPrint ? 1 : 0,
        ]);
    }

    /**
     * @return array{user: Member, qrSvg: string, autoPrint: bool}
     */
    public static function viewData(Member $member, bool $autoPrint = false): array
    {
        return [
            'user' => $member,
            'qrSvg' => MemberQrCode::printSvgMarkupFor($member),
            'autoPrint' => $autoPrint,
        ];
    }
}
