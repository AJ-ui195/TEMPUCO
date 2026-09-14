<?php

namespace App\Support;

use App\Models\Member;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class MemberQrCode
{
    public static function dataUriFor(Member $member, int $scale = 3): string
    {
        return self::renderDataUri(self::payloadFor($member), $scale);
    }

    public static function printDataUriFor(Member $member): string
    {
        return self::dataUriFor($member, scale: 10);
    }

    public static function printSvgMarkupFor(Member $member): string
    {
        $options = new QROptions([
            'scale' => 10,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
        ]);

        return (string) (new QRCode($options))->render(
            json_encode(self::payloadFor($member), JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array{member_id: int, name: string, email: string}
     */
    public static function payloadFor(Member $member): array
    {
        return [
            'member_id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
        ];
    }

    /**
     * @param  array{member_id: int, name: string, email: string}  $payload
     */
    private static function renderDataUri(array $payload, int $scale): string
    {
        $options = new QROptions([
            'scale' => $scale,
            'outputBase64' => true,
            'svgAddXmlHeader' => false,
        ]);

        return (string) (new QRCode($options))->render(
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}
