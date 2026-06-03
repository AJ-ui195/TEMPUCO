<?php

namespace App\Support;

use App\Models\User;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class MemberQrCode
{
    public static function dataUriFor(User $user, int $scale = 3): string
    {
        return self::renderDataUri(self::payloadFor($user), $scale);
    }

    public static function printDataUriFor(User $user): string
    {
        return self::dataUriFor($user, scale: 10);
    }

    public static function printSvgMarkupFor(User $user): string
    {
        $options = new QROptions([
            'scale' => 10,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
        ]);

        return (string) (new QRCode($options))->render(
            json_encode(self::payloadFor($user), JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array{member_id: int, name: string, email: string}
     */
    public static function payloadFor(User $user): array
    {
        return [
            'member_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
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
