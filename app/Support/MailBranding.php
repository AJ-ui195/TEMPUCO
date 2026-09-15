<?php

namespace App\Support;

final class MailBranding
{
    public static function logoUrl(): string
    {
        $configured = config('mail.logo_url');

        if (filled($configured)) {
            return (string) $configured;
        }

        $url = asset('images/DICNHSLOGO1.png');
        $host = parse_url($url, PHP_URL_HOST) ?? '';

        if (in_array($host, ['127.0.0.1', 'localhost', '[::1]'], true)) {
            return 'https://raw.githubusercontent.com/AJ-ui195/TEMPUCO/master/public/images/DICNHSLOGO1.png';
        }

        return $url;
    }
}
