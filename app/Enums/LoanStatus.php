<?php

namespace App\Enums;

enum LoanStatus: string
{
    case AwaitingVerification = 'awaiting_verification';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::AwaitingVerification => __('Awaiting email confirmation'),
            self::Pending => __('Pending review'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
        };
    }
}
