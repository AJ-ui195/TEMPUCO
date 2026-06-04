<?php

namespace App\Filament\Cashier\Pages;

use App\Support\PosSalesReport;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
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

    public function updatedPeriod(): void
    {
        //
    }

    public function report(): PosSalesReport
    {
        return new PosSalesReport($this->period, $this->year, $this->month);
    }

    /**
     * @return array{transaction_count: int, total_revenue: float, items_sold: int}
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

            fputcsv($handle, [
                __('Reference'),
                __('Date'),
                __('Time'),
                __('Cashier'),
                __('Items'),
                __('Total (PHP)'),
                __('Paid (PHP)'),
                __('Change (PHP)'),
            ]);

            foreach ($report->sales() as $sale) {
                $itemCount = $sale->items->sum('quantity');

                fputcsv($handle, [
                    $sale->reference,
                    $sale->created_at?->format('Y-m-d'),
                    $sale->created_at?->format('H:i:s'),
                    $sale->user?->name ?? '—',
                    $itemCount,
                    number_format((float) $sale->total, 2, '.', ''),
                    number_format((float) $sale->amount_paid, 2, '.', ''),
                    number_format((float) $sale->change_amount, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $report->exportFilename(), [
            'Content-Type' => 'text/csv',
        ]);
    }
}
