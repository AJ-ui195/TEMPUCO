<?php

namespace App\Support;

use App\Models\CancelledReceipt;
use App\Models\InvoiceFeePayment;
use App\Models\LoanPayment;
use App\Models\PosCreditPayment;

final class CollectionReceiptNumbers
{
    public static function nextOfficialReceiptNo(): string
    {
        return self::nextNumber([
            LoanPayment::query()->whereNotNull('official_receipt_no')->pluck('official_receipt_no'),
            PosCreditPayment::query()->where('receipt_kind', 'official_receipt')->pluck('reference'),
            CancelledReceipt::query()->where('kind', 'official_receipt')->pluck('number'),
        ]);
    }

    public static function nextInvoiceNo(): string
    {
        return self::nextNumber([
            PosCreditPayment::query()->whereNotNull('invoice_no')->pluck('invoice_no'),
            CancelledReceipt::query()->where('kind', 'invoice')->pluck('number'),
            InvoiceFeePayment::query()->pluck('invoice_no'),
        ]);
    }

    /**
     * @param  array<int, iterable<int, mixed>>  $series
     */
    private static function nextNumber(array $series): string
    {
        $max = 0;

        foreach ($series as $values) {
            foreach ($values as $value) {
                $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

                if ($digits === '') {
                    continue;
                }

                $max = max($max, (int) $digits);
            }
        }

        return str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }
}
