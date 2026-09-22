<?php

namespace App\Support;

use App\Models\Setting;
use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

final class MemberEditPin
{
    public const KEY = 'member_edit_pin';

    public const MIN_LENGTH = 4;

    public static function hashed(): ?string
    {
        $value = Setting::query()->where('key', self::KEY)->value('value');

        return filled($value) ? (string) $value : null;
    }

    public static function check(string $pin): bool
    {
        $hashed = self::hashed();

        if ($hashed === null) {
            return false;
        }

        return Hash::check($pin, $hashed);
    }

    public static function update(string $current, string $new): void
    {
        if (! self::check($current)) {
            throw new InvalidArgumentException(__('The current PIN is incorrect.'));
        }

        $new = trim($new);

        if (strlen($new) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(__('The new PIN must be at least :min characters.', [
                'min' => self::MIN_LENGTH,
            ]));
        }

        Setting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => Hash::make($new)],
        );
    }

    public static function formField(): TextInput
    {
        return TextInput::make('member_edit_pin')
            ->label(__('PIN'))
            ->password()
            ->revealable()
            ->required()
            ->dehydrated(false)
            ->helperText(__('Required to edit or delete a member.'))
            ->rules([
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (! self::check((string) $value)) {
                        $fail(__('The PIN is incorrect.'));
                    }
                },
            ]);
    }
}
