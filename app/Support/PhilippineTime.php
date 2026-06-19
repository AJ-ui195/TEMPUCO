<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class PhilippineTime
{
    public const TIMEZONE = 'Asia/Manila';

    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    public static function format(?CarbonInterface $datetime, string $format = 'M j, Y g:i A'): string
    {
        if ($datetime === null) {
            return '—';
        }

        return $datetime->copy()->timezone(self::TIMEZONE)->format($format);
    }

    public static function formatDate(?CarbonInterface $datetime): string
    {
        return static::format($datetime, 'Y-m-d');
    }

    public static function formatTime(?CarbonInterface $datetime): string
    {
        return static::format($datetime, 'H:i:s');
    }
}
