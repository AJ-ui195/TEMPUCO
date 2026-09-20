<?php

namespace App\Support;

use App\Enums\ReceiptKind;
use App\Models\CancelledReceipt;
use App\Models\InvoiceFeePayment;
use App\Models\LoanPayment;
use App\Models\PosCreditPayment;
use Illuminate\Support\Collection;

final class CollectionReceipts
{
    /**
     * @return array{loan: ?LoanPayment, canteen: ?PosCreditPayment}
     */
    public static function findPosted(string $number, string $kind): array
    {
        $number = trim($number);

        if ($number === '') {
            return ['loan' => null, 'canteen' => null];
        }

        $candidates = self::candidates($number);

        if ($kind === ReceiptKind::Invoice->value) {
            return [
                'loan' => null,
                'canteen' => PosCreditPayment::query()->whereIn('invoice_no', $candidates)->first(),
            ];
        }

        return [
            'loan' => LoanPayment::query()->whereIn('official_receipt_no', $candidates)->first(),
            'canteen' => PosCreditPayment::query()
                ->whereIn('reference', $candidates)
                ->first(),
        ];
    }

    public static function isCancelled(string $number, string $kind): bool
    {
        $candidates = self::candidates($number);

        if ($candidates === []) {
            return false;
        }

        return CancelledReceipt::query()
            ->where('kind', $kind)
            ->whereIn('number', $candidates)
            ->exists();
    }

    public static function isTaken(
        string $number,
        string $kind,
        ?int $exceptLoanPaymentId = null,
        ?int $exceptPosPaymentId = null,
        ?string $exceptInvoiceNo = null,
    ): bool {
        if (self::isCancelled($number, $kind)) {
            return true;
        }

        $hit = self::findPosted($number, $kind);
        $loan = $hit['loan'];
        $canteen = $hit['canteen'];

        if ($loan instanceof LoanPayment && $loan->id !== $exceptLoanPaymentId) {
            return true;
        }

        if ($canteen instanceof PosCreditPayment && $canteen->id !== $exceptPosPaymentId) {
            return true;
        }

        if ($kind === ReceiptKind::Invoice->value) {
            $except = self::candidates((string) $exceptInvoiceNo);
            $fees = self::findInvoiceFees($number);

            if ($fees->isNotEmpty()) {
                $current = (string) $fees->first()->invoice_no;

                if ($exceptInvoiceNo === null || ! in_array($current, $except, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return Collection<int, InvoiceFeePayment>
     */
    public static function findInvoiceFees(string $number): Collection
    {
        $candidates = self::candidates($number);

        if ($candidates === []) {
            return collect();
        }

        return InvoiceFeePayment::query()
            ->whereIn('invoice_no', $candidates)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return list<string>
     */
    public static function candidates(string $number): array
    {
        $number = trim($number);
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if ($number === '' && $digits === '') {
            return [];
        }

        $values = [$number];

        if ($digits !== '') {
            $stripped = ltrim($digits, '0') ?: '0';
            $padded = str_pad($digits, 6, '0', STR_PAD_LEFT);

            foreach ([$digits, $stripped, $padded] as $value) {
                $values[] = $value;
                $values[] = $value.'-OR';
                $values[] = $value.'-IN';
            }
        }

        return array_values(array_unique($values));
    }

    public static function findRegularLoanPayment(string $number): ?LoanPayment
    {
        $number = trim($number);
        $candidates = self::candidates($number);
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if ($candidates === [] && $digits === '') {
            return null;
        }

        $match = LoanPayment::query()
            ->whereNotNull('regular_loan_id')
            ->whereNotNull('official_receipt_no')
            ->whereIn('official_receipt_no', $candidates)
            ->orderByDesc('id')
            ->first();

        if ($match instanceof LoanPayment) {
            return $match;
        }

        if ($digits === '') {
            return null;
        }

        $padded = str_pad($digits, 6, '0', STR_PAD_LEFT);
        $stripped = ltrim($digits, '0') ?: '0';

        return LoanPayment::query()
            ->whereNotNull('regular_loan_id')
            ->whereNotNull('official_receipt_no')
            ->orderByDesc('id')
            ->get()
            ->first(function (LoanPayment $payment) use ($digits, $padded, $stripped): bool {
                $stored = preg_replace('/\D+/', '', (string) $payment->official_receipt_no) ?? '';

                if ($stored === '') {
                    return false;
                }

                $storedPadded = str_pad($stored, 6, '0', STR_PAD_LEFT);
                $storedStripped = ltrim($stored, '0') ?: '0';

                return $stored === $digits
                    || $storedPadded === $padded
                    || $storedStripped === $stripped;
            });
    }

    public static function currentNumber(string $kind, string $officialReceiptNo, string $invoiceNo): string
    {
        return trim($kind === ReceiptKind::Invoice->value ? $invoiceNo : $officialReceiptNo);
    }
}
