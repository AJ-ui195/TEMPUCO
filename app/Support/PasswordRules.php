<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class PasswordRules
{
    public static function rule(): Password
    {
        return Password::min(10)
            ->mixedCase()
            ->numbers();
    }

    public static function helperText(): string
    {
        return __('At least 10 characters, with mixed case and a number.');
    }
}
