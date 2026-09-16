<?php

namespace App\Support;

final class InvoiceFeeItems
{
    public const INTEREST = 'interest';

    public const SURCHARGE = 'surcharge';

    public const MEMBERSHIP_FEE = 'membership_fee';

    public const OTHERS = 'others';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::INTEREST => __('Interest'),
            self::SURCHARGE => __('Surcharge'),
            self::MEMBERSHIP_FEE => __('Membership fee'),
            self::OTHERS => __('Others'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }
}
