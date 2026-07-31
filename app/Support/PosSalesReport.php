<?php

namespace App\Support;

use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PosSalesReport
{
    public const PERIOD_TODAY = 'today';

    public const PERIOD_MONTH = 'month';

    public const PERIOD_YEAR = 'year';

    public const PERIOD_CUSTOM = 'custom';

    public function __construct(
        public string $period = self::PERIOD_TODAY,
        public ?int $year = null,
        public ?int $month = null,
        public ?string $fromDate = null,
        public ?string $toDate = null,
    ) {
        $this->year ??= now()->year;
        $this->month ??= now()->month;
    }

    public function rangeStart(): Carbon
    {
        return ($this->parseDate($this->fromDate) ?? now()->startOfMonth())->startOfDay();
    }

    public function rangeEnd(): Carbon
    {
        $end = $this->parseDate($this->toDate) ?? now()->endOfDay();
        $start = $this->rangeStart();

        return $end->lt($start) ? $start->copy()->endOfDay() : $end->endOfDay();
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

    /**
     * Sales in the selected period, without eager loads.
     *
     * @return Builder<PosSale>
     */
    public function baseQuery(): Builder
    {
        $query = PosSale::query();

        match ($this->period) {
            self::PERIOD_TODAY => $query->whereDate('created_at', today()),
            self::PERIOD_MONTH => $query
                ->whereYear('created_at', $this->year)
                ->whereMonth('created_at', $this->month),
            self::PERIOD_YEAR => $query->whereYear('created_at', $this->year),
            self::PERIOD_CUSTOM => $query->whereBetween('created_at', [$this->rangeStart(), $this->rangeEnd()]),
            default => $query->whereRaw('0 = 1'),
        };

        return $query;
    }

    /**
     * @return Builder<PosSale>
     */
    public function query(): Builder
    {
        return $this->baseQuery()
            ->with(['member', 'cashier', 'items.inventoryItem', 'items.canteenInventoryItem']);
    }

    /**
     * Sales settled at the register (excludes unpaid member credit charges).
     *
     * @return Builder<PosSale>
     */
    public function cashSalesQuery(): Builder
    {
        return $this->baseQuery()->where(function (Builder $query): void {
            $query->whereNull('member_id')
                ->orWhereColumn('amount_paid', '>=', 'total');
        });
    }

    /**
     * @return Builder<PosSale>
     */
    public function creditSalesQuery(): Builder
    {
        return $this->baseQuery()
            ->whereNotNull('member_id')
            ->whereColumn('amount_paid', '<', 'total');
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
        $allSales = $this->baseQuery();
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
     * Purchases grouped per member for the selected period, highest spend first.
     *
     * @return Collection<int, array{
     *     member_id: int,
     *     name: string,
     *     points: int,
     *     transaction_count: int,
     *     total_spent: float,
     *     total_paid: float,
     *     outstanding: float
     * }>
     */
    public function memberPurchases(): Collection
    {
        $rows = $this->baseQuery()
            ->whereNotNull('member_id')
            ->groupBy('member_id')
            ->select([
                'member_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(total) as total_spent'),
                // Change handed back on overpaid sales must not offset another sale's balance.
                DB::raw('SUM(CASE WHEN amount_paid < total THEN total - amount_paid ELSE 0 END) as outstanding'),
            ])
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $members = User::query()
            ->whereIn('id', $rows->pluck('member_id'))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function (PosSale $row) use ($members): array {
                $member = $members->get($row->member_id);
                $spent = round((float) $row->total_spent, 2);
                $outstanding = round((float) $row->outstanding, 2);

                return [
                    'member_id' => (int) $row->member_id,
                    'name' => $member?->name ?? __('Unknown member'),
                    'points' => (int) ($member?->points ?? 0),
                    'transaction_count' => (int) $row->transaction_count,
                    'total_spent' => $spent,
                    'total_paid' => round($spent - $outstanding, 2),
                    'outstanding' => $outstanding,
                ];
            })
            ->sortByDesc('total_spent')
            ->values();
    }

    public function memberPurchasesTotal(): float
    {
        return round((float) $this->memberPurchases()->sum('total_spent'), 2);
    }

    /**
     * Products sold in the period, aggregated by inventory item.
     *
     * @return Collection<int, array{name: string, sku: ?string, quantity_sold: int, revenue: float}>
     */
    public function itemsSoldByProduct(): Collection
    {
        $allSaleIds = (clone $this->baseQuery())->select('id');
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
            self::PERIOD_CUSTOM => $this->rangeStart()->format('Y-m-d').'-to-'.$this->rangeEnd()->format('Y-m-d'),
            default => 'sales',
        };

        return "pos-sales-{$slug}.csv";
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
