<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\PosSale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BirDailySalesReport
{
    public Carbon $from;

    public Carbon $to;

    public function __construct(
        Carbon|string|null $from = null,
        Carbon|string|null $to = null,
        public PosSaleChannel $saleChannel = PosSaleChannel::Grocery,
    ) {
        $now = PhilippineTime::now();

        $this->from = $this->parseDate($from)?->startOfDay()
            ?? $now->copy()->startOfMonth()->startOfDay();
        $this->to = $this->parseDate($to)?->endOfDay()
            ?? $now->copy()->endOfMonth()->endOfDay();

        if ($this->from->gt($this->to)) {
            [$this->from, $this->to] = [
                $this->to->copy()->startOfDay(),
                $this->from->copy()->endOfDay(),
            ];
        }
    }

    public function fromDate(): Carbon
    {
        return $this->from->copy();
    }

    public function toDate(): Carbon
    {
        return $this->to->copy();
    }

    public function fromLabel(): string
    {
        return $this->from->format('m/d/Y');
    }

    public function toLabel(): string
    {
        return $this->to->format('m/d/Y');
    }

    /**
     * One row per calendar day in the selected range.
     *
     * @return Collection<int, array{date: string, last_si_no: string, net_amount: float}>
     */
    public function rows(): Collection
    {
        $from = $this->fromDate()->startOfDay();
        $to = $this->toDate()->endOfDay();

        $daily = PosSale::query()
            ->where('sale_channel', $this->saleChannel)
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->selectRaw('DATE(created_at) as sale_date')
            ->selectRaw('SUM(total) as net_amount')
            ->selectRaw('MAX(id) as last_sale_id')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy(fn ($row): string => (string) $row->sale_date);

        $lastReferences = PosSale::query()
            ->whereIn('id', $daily->pluck('last_sale_id')->filter())
            ->pluck('reference', 'id');

        $rows = collect();
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $day = $daily->get($key);
            $lastSaleId = $day?->last_sale_id;
            $reference = $lastSaleId ? (string) ($lastReferences[$lastSaleId] ?? '') : '';

            $rows->push([
                'date' => $cursor->format('m/d/Y'),
                'last_si_no' => $reference !== '' ? $reference : ($day ? '0' : ''),
                'net_amount' => round((float) ($day->net_amount ?? 0), 2),
            ]);

            $cursor->addDay();
        }

        return $rows;
    }

    public function grandTotal(?Collection $rows = null): float
    {
        $rows ??= $this->rows();

        return round((float) $rows->sum('net_amount'), 2);
    }

    private function parseDate(Carbon|string|null $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy()->timezone(PhilippineTime::TIMEZONE);
        }

        return Carbon::parse($value, PhilippineTime::TIMEZONE);
    }
}
