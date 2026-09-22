<?php

namespace App\Support;

use App\Enums\ReceiptKind;
use App\Models\InvoiceFeePayment;
use App\Models\LoanPayment;
use App\Models\PosCreditPayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CollectionReport
{
    public const PERIOD_TODAY = 'today';

    public const PERIOD_MONTH = 'month';

    public const PERIOD_YEAR = 'year';

    public const PERIOD_CUSTOM = 'custom';

    public const KIND_ALL = 'all';

    public const KIND_OR = 'or';

    public const KIND_IN = 'in';

    public const OR_COLUMNS = [
        'character_short_term',
        'character',
        'salary_loan',
        'share_capital',
        'insurance',
        'quick_loan',
        'others',
    ];

    public function __construct(
        public string $period = self::PERIOD_TODAY,
        public ?int $year = null,
        public ?int $month = null,
        public ?string $fromDate = null,
        public ?string $toDate = null,
        public string $receiptKind = self::KIND_ALL,
    ) {
        $this->year ??= now()->year;
        $this->month ??= now()->month;

        if (! in_array($this->receiptKind, [self::KIND_ALL, self::KIND_OR, self::KIND_IN], true)) {
            $this->receiptKind = self::KIND_ALL;
        }
    }

    public function rangeStart(): Carbon
    {
        return match ($this->period) {
            self::PERIOD_TODAY => now()->startOfDay(),
            self::PERIOD_MONTH => Carbon::createFromDate($this->year, $this->month, 1)->startOfDay(),
            self::PERIOD_YEAR => Carbon::createFromDate($this->year, 1, 1)->startOfDay(),
            default => ($this->parseDate($this->fromDate) ?? now()->startOfMonth())->startOfDay(),
        };
    }

    public function rangeEnd(): Carbon
    {
        return match ($this->period) {
            self::PERIOD_TODAY => now()->endOfDay(),
            self::PERIOD_MONTH => Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth()->endOfDay(),
            self::PERIOD_YEAR => Carbon::createFromDate($this->year, 12, 31)->endOfDay(),
            default => $this->customRangeEnd(),
        };
    }

    public function printedDateRange(): string
    {
        return $this->rangeStart()->format('m/d/Y').' to '.$this->rangeEnd()->format('m/d/Y');
    }

    public function periodLabel(): string
    {
        return match ($this->period) {
            self::PERIOD_TODAY => __('Today').' ('.now()->format('M j, Y').')',
            self::PERIOD_MONTH => Carbon::createFromDate($this->year, $this->month, 1)->format('F Y'),
            self::PERIOD_YEAR => (string) $this->year,
            self::PERIOD_CUSTOM => $this->rangeStart()->format('M j, Y').' — '.$this->rangeEnd()->format('M j, Y'),
            default => __('Custom'),
        };
    }

    public function receiptKindLabel(): string
    {
        return match ($this->receiptKind) {
            self::KIND_OR => __('O.R.'),
            self::KIND_IN => __('Invoice'),
            default => __('All'),
        };
    }

    public function exportFilename(): string
    {
        return 'collection-report-'.$this->receiptKind.'-'.now()->format('Y-m-d').'.csv';
    }

    public function includesOfficialReceipts(): bool
    {
        return $this->receiptKind !== self::KIND_IN;
    }

    public function includesInvoices(): bool
    {
        return $this->receiptKind !== self::KIND_OR;
    }

    /**
     * @return array{
     *     transaction_count: int,
     *     total: float,
     *     loan_total: float,
     *     invoice_total: float,
     *     credit_total: float
     * }
     */
    public function summary(): array
    {
        $or = $this->officialReceiptRows();
        $in = $this->invoiceSheetRows();

        return [
            'transaction_count' => $or->count() + $in->count(),
            'total' => round((float) $or->sum('total') + (float) $in->sum('total'), 2),
            'loan_total' => round((float) $or->sum('total') - (float) $or->sum('credit_in_others'), 2),
            'invoice_total' => round((float) $in->sum('total'), 2),
            'credit_total' => round((float) $or->sum('credit_in_others'), 2),
        ];
    }

    /**
     * @return Collection<int, array{
     *     date: mixed,
     *     name: string,
     *     or_no: string,
     *     character_short_term: float,
     *     character: float,
     *     salary_loan: float,
     *     share_capital: float,
     *     insurance: float,
     *     quick_loan: float,
     *     others: float,
     *     credit_in_others: float,
     *     total: float
     * }>
     */
    public function officialReceiptRows(): Collection
    {
        if (! $this->includesOfficialReceipts()) {
            return collect();
        }

        $grouped = [];

        foreach ($this->loanPayments() as $payment) {
            if ($payment->receipt_kind === ReceiptKind::Landbank->value) {
                continue;
            }
            $amount = round((float) $payment->amount, 2);
            $orNo = CollectionReceiptNumbers::displayOfficialReceipt((string) ($payment->official_receipt_no ?? ''));
            $member = (string) ($payment->loan()?->member?->name ?? '—');
            $key = $orNo !== '' ? 'or:'.$orNo.':'.$member : 'loan:'.$payment->id;
            $bucket = $this->orBucket($payment);

            $grouped[$key] = $this->blankOrRow(
                $grouped[$key] ?? null,
                $payment->received_at ?? $payment->created_at,
                $member,
                $orNo,
            );
            $grouped[$key][$bucket] = round($grouped[$key][$bucket] + $amount, 2);
            $grouped[$key]['total'] = round($grouped[$key]['total'] + $amount, 2);
        }

        foreach ($this->creditPayments() as $payment) {
            if ($this->creditIsInvoice($payment)) {
                continue;
            }

            $amount = round((float) $payment->amount, 2);
            $orNo = CollectionReceiptNumbers::displayOfficialReceipt((string) ($payment->reference ?: $payment->invoice_no));
            $member = (string) ($payment->member?->name ?? '—');
            $key = $orNo !== '' ? 'or:'.$orNo.':'.$member : 'credit:'.$payment->id;

            $grouped[$key] = $this->blankOrRow(
                $grouped[$key] ?? null,
                $payment->created_at,
                $member,
                $orNo,
            );
            $grouped[$key]['others'] = round($grouped[$key]['others'] + $amount, 2);
            $grouped[$key]['credit_in_others'] = round($grouped[$key]['credit_in_others'] + $amount, 2);
            $grouped[$key]['total'] = round($grouped[$key]['total'] + $amount, 2);
        }

        return collect(array_values($grouped))
            ->sortBy([
                ['date', 'asc'],
                ['or_no', 'asc'],
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{
     *     date: mixed,
     *     name: string,
     *     or_no: string,
     *     interest: float,
     *     surcharge: float,
     *     membership_fee: float,
     *     others: float,
     *     total: float
     * }>
     */
    public function invoiceSheetRows(): Collection
    {
        if (! $this->includesInvoices()) {
            return collect();
        }

        $rows = $this->constrain(InvoiceFeePayment::query()->with('member'), 'received_at')
            ->get()
            ->map(function (InvoiceFeePayment $invoice): array {
                $interest = round((float) $invoice->interest, 2);
                $surcharge = round((float) $invoice->surcharge, 2);
                $membership = round((float) $invoice->membership_fee, 2);
                $others = round((float) $invoice->others, 2);
                $total = round((float) ($invoice->amount ?: $invoice->totalAmount()), 2);

                return [
                    'date' => $invoice->received_at ?? $invoice->created_at,
                    'name' => (string) ($invoice->member?->name ?? '—'),
                    'or_no' => CollectionReceiptNumbers::displayInvoice((string) $invoice->invoice_no),
                    'interest' => $interest,
                    'surcharge' => $surcharge,
                    'membership_fee' => $membership,
                    'others' => $others,
                    'total' => $total,
                ];
            });

        foreach ($this->creditPayments() as $payment) {
            if (! $this->creditIsInvoice($payment)) {
                continue;
            }

            $amount = round((float) $payment->amount, 2);
            $rows->push([
                'date' => $payment->created_at,
                'name' => (string) ($payment->member?->name ?? '—'),
                'or_no' => CollectionReceiptNumbers::displayInvoice((string) ($payment->invoice_no ?: $payment->reference)),
                'interest' => 0.0,
                'surcharge' => 0.0,
                'membership_fee' => 0.0,
                'others' => $amount,
                'total' => $amount,
            ]);
        }

        return $rows
            ->sortBy([
                ['date', 'asc'],
                ['or_no', 'asc'],
            ])
            ->values();
    }

    /**
     * @return array<string, float>
     */
    public function officialReceiptTotals(): array
    {
        $totals = array_fill_keys([...self::OR_COLUMNS, 'total'], 0.0);

        foreach ($this->officialReceiptRows() as $row) {
            foreach (self::OR_COLUMNS as $column) {
                $totals[$column] = round($totals[$column] + $row[$column], 2);
            }
            $totals['total'] = round($totals['total'] + $row['total'], 2);
        }

        return $totals;
    }

    /**
     * @return array{interest: float, surcharge: float, membership_fee: float, others: float, total: float}
     */
    public function invoiceSheetTotals(): array
    {
        $totals = [
            'interest' => 0.0,
            'surcharge' => 0.0,
            'membership_fee' => 0.0,
            'others' => 0.0,
            'total' => 0.0,
        ];

        foreach ($this->invoiceSheetRows() as $row) {
            foreach (array_keys($totals) as $column) {
                $totals[$column] = round($totals[$column] + $row[$column], 2);
            }
        }

        return $totals;
    }

    /**
     * @return Collection<int, LoanPayment>
     */
    private function loanPayments(): Collection
    {
        return $this->constrain(
            LoanPayment::query()->with([
                'characterLoan.member',
                'quickLoan.member',
                'regularLoan.member',
            ]),
            'received_at',
        )->get();
    }

    /**
     * @return Collection<int, PosCreditPayment>
     */
    private function creditPayments(): Collection
    {
        return $this->constrain(PosCreditPayment::query()->with('member'), 'created_at')->get();
    }

    /**
     * @param  array<string, mixed>|null  $existing
     * @return array<string, mixed>
     */
    private function blankOrRow(?array $existing, mixed $date, string $name, string $orNo): array
    {
        if (is_array($existing)) {
            return $existing;
        }

        return [
            'date' => $date,
            'name' => $name,
            'or_no' => $orNo,
            'character_short_term' => 0.0,
            'character' => 0.0,
            'salary_loan' => 0.0,
            'share_capital' => 0.0,
            'insurance' => 0.0,
            'quick_loan' => 0.0,
            'others' => 0.0,
            'credit_in_others' => 0.0,
            'total' => 0.0,
        ];
    }

    private function orBucket(LoanPayment $payment): string
    {
        if ($payment->quick_loan_id) {
            return 'quick_loan';
        }

        if ($payment->regular_loan_id) {
            return 'salary_loan';
        }

        $type = strtoupper(trim((string) ($payment->loan()?->loan_type ?? '')));

        if (LoanTypes::isCharacterShortTerm($type) || LoanTypes::isRetireeShortTerm($type)) {
            return 'character_short_term';
        }

        if (LoanTypes::isCharacter($type) || LoanTypes::isCharacterEmergency($type)) {
            return 'character';
        }

        return 'others';
    }

    private function creditIsInvoice(PosCreditPayment $payment): bool
    {
        return $payment->receipt_kind === ReceiptKind::Invoice->value
            || filled($payment->invoice_no);
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    private function constrain(Builder $query, string $column): Builder
    {
        return match ($this->period) {
            self::PERIOD_TODAY => $query->whereDate($column, today()),
            self::PERIOD_MONTH => $query
                ->whereYear($column, $this->year)
                ->whereMonth($column, $this->month),
            self::PERIOD_YEAR => $query->whereYear($column, $this->year),
            self::PERIOD_CUSTOM => $query->whereBetween($column, [$this->rangeStart(), $this->rangeEnd()]),
            default => $query->whereRaw('0 = 1'),
        };
    }

    private function customRangeEnd(): Carbon
    {
        $end = $this->parseDate($this->toDate) ?? now()->endOfDay();
        $start = $this->rangeStart();

        return $end->lt($start) ? $start->copy()->endOfDay() : $end->endOfDay();
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
