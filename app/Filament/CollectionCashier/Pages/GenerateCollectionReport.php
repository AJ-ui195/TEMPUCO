<?php

namespace App\Filament\CollectionCashier\Pages;

use App\Support\CollectionReport;
use App\Support\PhilippineTime;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerateCollectionReport extends Page
{
    public const PERIOD_TODAY = CollectionReport::PERIOD_TODAY;

    public const PERIOD_MONTH = CollectionReport::PERIOD_MONTH;

    public const PERIOD_YEAR = CollectionReport::PERIOD_YEAR;

    public const PERIOD_CUSTOM = CollectionReport::PERIOD_CUSTOM;

    public const KIND_ALL = CollectionReport::KIND_ALL;

    public const KIND_OR = CollectionReport::KIND_OR;

    public const KIND_IN = CollectionReport::KIND_IN;

    protected static ?string $navigationLabel = 'Generate Collection report';

    protected static ?string $title = 'Generate Collection report';

    protected static ?string $slug = 'generate-collection-report';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.collection-cashier.generate-collection-report';

    public string $period = self::PERIOD_TODAY;

    public int $year;

    public int $month;

    public string $fromDate = '';

    public string $toDate = '';

    public string $receiptKind = self::KIND_ALL;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Generate Collection report');
    }

    public function report(): CollectionReport
    {
        return new CollectionReport(
            $this->period,
            $this->year,
            $this->month,
            $this->fromDate,
            $this->toDate,
            $this->receiptKind,
        );
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
    public function getSummary(): array
    {
        return $this->report()->summary();
    }

    public function exportCsv(): StreamedResponse
    {
        $report = $this->report();

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            $money = static fn (float $amount): string => number_format($amount, 2, '.', '');

            if ($report->includesOfficialReceipts()) {
                fputcsv($handle, [__('CASHIER\'S COLLECTION REPORT'), __('O.R.'), $report->printedDateRange()]);
                fputcsv($handle, [
                    __('Date'),
                    __('Name'),
                    __('OR #'),
                    __('Character Short-term'),
                    __('Character'),
                    __('Salary loan'),
                    __('Share Capital'),
                    __('Insurance'),
                    __('Quick Loan'),
                    __('Others'),
                    __('Total'),
                ]);

                foreach ($report->officialReceiptRows() as $row) {
                    fputcsv($handle, [
                        PhilippineTime::format($row['date'], 'm/d/Y'),
                        $row['name'],
                        $row['or_no'],
                        $money($row['character_short_term']),
                        $money($row['character']),
                        $money($row['salary_loan']),
                        $money($row['share_capital']),
                        $money($row['insurance']),
                        $money($row['quick_loan']),
                        $money($row['others']),
                        $money($row['total']),
                    ]);
                }

                $orTotals = $report->officialReceiptTotals();
                fputcsv($handle, [
                    '',
                    '',
                    __('Total'),
                    $money($orTotals['character_short_term']),
                    $money($orTotals['character']),
                    $money($orTotals['salary_loan']),
                    $money($orTotals['share_capital']),
                    $money($orTotals['insurance']),
                    $money($orTotals['quick_loan']),
                    $money($orTotals['others']),
                    $money($orTotals['total']),
                ]);
                fputcsv($handle, []);
            }

            if ($report->includesInvoices()) {
                fputcsv($handle, [__('CASHIER\'S COLLECTION REPORT'), __('Invoice'), $report->printedDateRange()]);
                fputcsv($handle, [
                    __('Date'),
                    __('Name'),
                    __('OR #'),
                    __('Interest'),
                    __('Surcharge'),
                    __('Membership fee'),
                    __('Others'),
                    __('Total'),
                ]);

                foreach ($report->invoiceSheetRows() as $row) {
                    fputcsv($handle, [
                        PhilippineTime::format($row['date'], 'm/d/Y'),
                        $row['name'],
                        $row['or_no'],
                        $money($row['interest']),
                        $money($row['surcharge']),
                        $money($row['membership_fee']),
                        $money($row['others']),
                        $money($row['total']),
                    ]);
                }

                $inTotals = $report->invoiceSheetTotals();
                fputcsv($handle, [
                    '',
                    '',
                    __('Total'),
                    $money($inTotals['interest']),
                    $money($inTotals['surcharge']),
                    $money($inTotals['membership_fee']),
                    $money($inTotals['others']),
                    $money($inTotals['total']),
                ]);
            }

            fclose($handle);
        }, $report->exportFilename(), [
            'Content-Type' => 'text/csv',
        ]);
    }
}
