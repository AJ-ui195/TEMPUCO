<?php

namespace App\Support;

use App\Models\PosSale;
use App\Models\PosSaleItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PosSalesReport
{
    public const PERIOD_TODAY = 'today';

    public const PERIOD_MONTH = 'month';

    public const PERIOD_YEAR = 'year';

    public function __construct(
        public string $period = self::PERIOD_TODAY,
        public ?int $year = null,
        public ?int $month = null,
    ) {
        $this->year ??= now()->year;
        $this->month ??= now()->month;
    }

    public function periodLabel(): string
    {
        return match ($this->period) {
            self::PERIOD_TODAY => __('Today').' ('.now()->format('M j, Y').')',
            self::PERIOD_MONTH => Carbon::createFromDate($this->year, $this->month, 1)->format('F Y'),
            self::PERIOD_YEAR => (string) $this->year,
            default => __('Custom'),
        };
    }

    public function query(): Builder
    {
        $query = PosSale::query()->with(['user', 'items']);

        return match ($this->period) {
            self::PERIOD_TODAY => $query->whereDate('created_at', today()),
            self::PERIOD_MONTH => $query
                ->whereYear('created_at', $this->year)
                ->whereMonth('created_at', $this->month),
            self::PERIOD_YEAR => $query->whereYear('created_at', $this->year),
            default => $query->whereRaw('0 = 1'),
        };
    }

    /**
     * @return array{transaction_count: int, total_revenue: float, items_sold: int}
     */
    public function summary(): array
    {
        $salesQuery = $this->query();

        $saleIds = (clone $salesQuery)->select('id');

        $itemsSold = (int) PosSaleItem::query()
            ->whereIn('pos_sale_id', $saleIds)
            ->sum('quantity');

        return [
            'transaction_count' => (clone $salesQuery)->count(),
            'total_revenue' => (float) (clone $salesQuery)->sum('total'),
            'items_sold' => $itemsSold,
        ];
    }

    /**
     * @return Collection<int, PosSale>
     */
    public function sales(): Collection
    {
        return $this->query()
            ->latest()
            ->get();
    }

    public function exportFilename(): string
    {
        $slug = match ($this->period) {
            self::PERIOD_TODAY => 'today-'.now()->format('Y-m-d'),
            self::PERIOD_MONTH => sprintf('%04d-%02d', $this->year, $this->month),
            self::PERIOD_YEAR => (string) $this->year,
            default => 'sales',
        };

        return "pos-sales-{$slug}.csv";
    }
}
