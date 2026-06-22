<?php

namespace App\Filament\Cashier\Pages;

use App\Support\PhilippineTime;
use App\Support\PosSalesReport;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PosSalesReportPage extends Page
{
    public const PERIOD_TODAY = PosSalesReport::PERIOD_TODAY;

    public const PERIOD_MONTH = PosSalesReport::PERIOD_MONTH;

    public const PERIOD_YEAR = PosSalesReport::PERIOD_YEAR;

    protected static ?string $navigationLabel = 'Sales reports';

    protected static ?string $title = 'Sales reports';

    protected static ?string $slug = 'sales-reports';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected string $view = 'filament.cashier.pos-sales-report';

    public string $period = self::PERIOD_TODAY;

    public int $year;

    public int $month;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Sales reports');
    }

    public function report(): PosSalesReport
    {
        return new PosSalesReport($this->period, $this->year, $this->month);
    }

    /**
     * @return array{
     *     transaction_count: int,
     *     cash_transaction_count: int,
     *     credit_transaction_count: int,
     *     total_revenue: float,
     *     credit_sales_total: float,
     *     items_sold: int
     * }
     */
    public function getSummary(): array
    {
        return $this->report()->summary();
    }

    /**
     * @return Collection<int, array{name: string, sku: ?string, quantity_sold: int, revenue: float}>
     */
    public function getItemsSoldByProduct(): Collection
    {
        return $this->report()->itemsSoldByProduct();
    }

    public function exportCsv(): StreamedResponse
    {
        $report = $this->report();

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [__('Items sold (summary)')]);
            fputcsv($handle, [
                __('Product'),
                __('SKU'),
                __('Quantity sold'),
                __('Revenue (PHP)'),
            ]);

            foreach ($report->itemsSoldByProduct() as $item) {
                fputcsv($handle, [
                    $item['name'],
                    $item['sku'] ?? '',
                    $item['quantity_sold'],
                    number_format($item['revenue'], 2, '.', ''),
                ]);
            }

            $summary = $report->summary();

            fputcsv($handle, []);
            fputcsv($handle, [__('Cash sales total (PHP)'), number_format($summary['total_revenue'], 2, '.', '')]);
            fputcsv($handle, [__('Credit sales total — excluded (PHP)'), number_format($summary['credit_sales_total'], 2, '.', '')]);

            fputcsv($handle, []);
            fputcsv($handle, [__('Transactions')]);
            fputcsv($handle, [
                __('Reference'),
                __('Date'),
                __('Time'),
                __('Payment'),
                __('Member / Cashier'),
                __('Total (PHP)'),
                __('Paid (PHP)'),
                __('Outstanding (PHP)'),
                __('Change (PHP)'),
            ]);

            foreach ($report->sales() as $sale) {
                fputcsv($handle, [
                    $sale->reference,
                    PhilippineTime::formatDate($sale->created_at),
                    PhilippineTime::formatTime($sale->created_at),
                    $sale->paymentTypeLabel(),
                    $sale->isCreditSale()
                        ? __('Credit — :member', ['member' => $sale->reportPartyLabel()])
                        : $sale->reportPartyLabel(),
                    number_format((float) $sale->total, 2, '.', ''),
                    number_format((float) $sale->amount_paid, 2, '.', ''),
                    $sale->isCreditSale()
                        ? number_format($sale->outstandingAmount(), 2, '.', '')
                        : '',
                    $sale->isCreditSale()
                        ? ''
                        : number_format((float) $sale->change_amount, 2, '.', ''),
                ]);

                fputcsv($handle, [
                    '',
                    __('Product'),
                    __('SKU'),
                    __('Qty'),
                    __('Unit price (PHP)'),
                    __('Line total (PHP)'),
                ]);

                foreach ($sale->items as $line) {
                    fputcsv($handle, [
                        '',
                        $line->productName(),
                        $line->productSku() ?? '',
                        $line->quantity,
                        number_format((float) $line->unit_price, 2, '.', ''),
                        number_format((float) $line->line_total, 2, '.', ''),
                    ]);
                }
            }

            fclose($handle);
        }, $report->exportFilename(), [
            'Content-Type' => 'text/csv',
        ]);
    }
}
