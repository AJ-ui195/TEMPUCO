<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        $query = PosSale::query()->with(['user', 'items.inventoryItem', 'items.canteenInventoryItem']);

        match ($this->period) {
            self::PERIOD_TODAY => $query->whereDate('created_at', today()),
            self::PERIOD_MONTH => $query
                ->whereYear('created_at', $this->year)
                ->whereMonth('created_at', $this->month),
            self::PERIOD_YEAR => $query->whereYear('created_at', $this->year),
            default => $query->whereRaw('0 = 1'),
        };
        return $query;
    }

    /**
     * Sales paid in cash at the register (excludes member credit charges).
     *
     * @return Builder<PosSale>
     */
    public function cashSalesQuery(): Builder
    {
        return $this->query()->whereHas('user', function (Builder $query): void {
            $query->where($query->qualifyColumn('role'), '!=', UserRole::User);
        });
    }

    /**
     * @return Builder<PosSale>
     */
    public function creditSalesQuery(): Builder
    {
        return $this->query()->whereHas('user', function (Builder $query): void {
            $query->where($query->qualifyColumn('role'), UserRole::User);
        });
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
    public function summary(): array
    {
        $allSales = $this->query();
        $cashSales = $this->cashSalesQuery();
        $creditSales = $this->creditSalesQuery();

        $saleIds = (clone $allSales)->select('id');

        $itemsSold = (int) PosSaleItem::query()
            ->whereIn('pos_sale_id', $saleIds)
            ->sum('quantity');

        $transactionCount = (clone $allSales)->count();
        $cashTransactionCount = (clone $cashSales)->count();

        return [
            'transaction_count' => $transactionCount,
            'cash_transaction_count' => $cashTransactionCount,
            'credit_transaction_count' => $transactionCount - $cashTransactionCount,
            'total_revenue' => (float) (clone $cashSales)->sum('total'),
            'credit_sales_total' => (float) (clone $creditSales)->sum('total'),
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

    /**
     * Products sold in the period, aggregated by inventory item.
     *
     * @return Collection<int, array{name: string, sku: ?string, quantity_sold: int, revenue: float}>
     */
    public function itemsSoldByProduct(): Collection
    {
        $allSaleIds = (clone $this->query())->select('id');
        $cashSaleIds = (clone $this->cashSalesQuery())->select('id');

        $rows = PosSaleItem::query()
            ->select([
                'pos_inventory_item_id',
                DB::raw('SUM(quantity) as quantity_sold'),
            ])
            ->whereNotNull('pos_inventory_item_id')
            ->with('inventoryItem:id,name,sku')
            ->whereIn('pos_sale_id', $allSaleIds)
            ->groupBy('pos_inventory_item_id')
            ->orderByDesc('quantity_sold')
            ->get();

        $cashRevenueByItem = PosSaleItem::query()
            ->select([
                'pos_inventory_item_id',
                DB::raw('SUM(line_total) as revenue'),
            ])
            ->whereIn('pos_sale_id', $cashSaleIds)
            ->whereNotNull('pos_inventory_item_id')
            ->groupBy('pos_inventory_item_id')
            ->pluck('revenue', 'pos_inventory_item_id');

        return $rows->map(fn (PosSaleItem $row): array => [
            'name' => $row->catalogProduct()?->name ?? __('Unknown product'),
            'sku' => $row->catalogProduct()?->sku,
            'quantity_sold' => (int) $row->quantity_sold,
            'revenue' => (float) ($cashRevenueByItem[$row->pos_inventory_item_id] ?? 0),
        ]);
    }

    public function cashSalesGrandTotal(): float
    {
        return (float) (clone $this->cashSalesQuery())->sum('total');
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
