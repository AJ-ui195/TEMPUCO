<?php

namespace App\Enums;

enum LoanPurpose: string
{
    case Personal = 'personal';
    case Emergency = 'emergency';
    case SchoolRelated = 'school_related';
    case Medical = 'medical';
    case Others = 'others';

    public function getLabel(): string
    {
        return match ($this) {
            self::Personal => __('Personal'),
            self::Emergency => __('Emergency'),
            self::SchoolRelated => __('School-related'),
            self::Medical => __('Medical'),
            self::Others => __('Others'),
        };
    }
}
