<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\PosCreditPayment;
use App\Models\PosSale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Running account history for one member. Entries are derived from existing
 * rows — credit charges from `pos_sales`, settlements from
 * `pos_credit_payments` — so no ledger table is stored.
 */
final class MemberCreditLedger
{
    public function __construct(
        public User $member,
    ) {}

    /**
     * Balance carried into the period, so a date-filtered ledger still adds up.
     */
    public function openingBalance(?PosSaleChannel $channel = null, ?Carbon $from = null): float
    {
        if ($from === null) {
            return 0.0;
        }

        $charged = (float) $this->chargesQuery($channel)
            ->where('created_at', '<', $from)
            ->selectRaw('SUM(total - amount_paid) as charged')
            ->value('charged');

        $paid = (float) $this->paymentsQuery($channel)
            ->where('created_at', '<', $from)
            ->sum('amount');

        return round($charged - $paid, 2);
    }

    /**
     * Charges and payments in the order they happened, with the balance owed
     * after each one.
     *
     * @return Collection<int, array{
     *     type: string,
     *     date: Carbon,
     *     reference: string,
     *     channel: ?PosSaleChannel,
     *     description: string,
     *     charge: float,
     *     payment: float,
     *     balance: float
     * }>
     */
    public function entries(?PosSaleChannel $channel = null, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $charges = $this->chargesQuery($channel)
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to))
            ->withSum(['items as items_quantity' => fn (Builder $items) => $items->notVoided()], 'quantity')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (PosSale $sale): array => [
                'type' => 'charge',
                'date' => $sale->created_at,
                'reference' => $sale->reference,
                'channel' => $sale->sale_channel,
                'description' => trans_choice(
                    'Charged to account · :count item|Charged to account · :count items',
                    (int) $sale->items_quantity,
                    ['count' => (int) $sale->items_quantity],
                ),
                'charge' => $sale->outstandingAmount(),
                'payment' => 0.0,
                'sort_key' => 0,
            ]);

        $payments = $this->paymentsQuery($channel)
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to))
            ->with('cashier')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (PosCreditPayment $payment): array => [
                'type' => 'payment',
                'date' => $payment->created_at,
                'reference' => $payment->reference,
                'channel' => $payment->sale_channel,
                'description' => $payment->cashier
                    ? __('Payment received by :cashier', ['cashier' => $payment->cashier->name])
                    : __('Payment received'),
                'charge' => 0.0,
                'payment' => (float) $payment->amount,
                'sort_key' => 1,
            ]);

        $balance = $this->openingBalance($channel, $from);

        return $charges
            ->concat($payments)
            ->sortBy([
                fn (array $a, array $b): int => $a['date'] <=> $b['date'],
                fn (array $a, array $b): int => $a['sort_key'] <=> $b['sort_key'],
            ])
            ->values()
            ->map(function (array $entry) use (&$balance): array {
                $balance = round($balance + $entry['charge'] - $entry['payment'], 2);

                unset($entry['sort_key']);

                return [...$entry, 'balance' => $balance];
            });
    }

    /**
     * @return array{opening: float, charged: float, paid: float, balance: float, entry_count: int}
     */
    public function summary(?PosSaleChannel $channel = null, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $entries = $this->entries($channel, $from, $to);
        $opening = $this->openingBalance($channel, $from);

        $charged = round((float) $entries->sum('charge'), 2);
        $paid = round((float) $entries->sum('payment'), 2);

        return [
            'opening' => $opening,
            'charged' => $charged,
            'paid' => $paid,
            'balance' => round(max(0, $opening + $charged - $paid), 2),
            'entry_count' => $entries->count(),
        ];
    }

    /**
     * Members with account activity in the period, or a balance brought into it.
     *
     * @return Collection<int, User>
     */
    public static function membersWithActivity(?PosSaleChannel $channel = null, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $inRange = fn (Builder $query): Builder => $query
            ->when($channel, fn (Builder $inner) => $inner->where('sale_channel', $channel->value))
            ->when($from, fn (Builder $inner) => $inner->where('created_at', '>=', $from))
            ->when($to, fn (Builder $inner) => $inner->where('created_at', '<=', $to));

        $memberIds = $inRange(PosSale::query()->whereNotNull('member_id')->whereColumn('amount_paid', '<', 'total'))
            ->distinct()
            ->pluck('member_id')
            ->concat(
                $inRange(PosCreditPayment::query())->distinct()->pluck('member_id')
            )
            ->concat(self::membersCarryingBalanceInto($channel, $from));

        $memberIds = $memberIds->unique()->values();

        if ($memberIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->members()
            ->whereIn('id', $memberIds)
            ->orderedByName()
            ->get();
    }

    /**
     * Member IDs that already owed something when the period started.
     *
     * @return Collection<int, int>
     */
    private static function membersCarryingBalanceInto(?PosSaleChannel $channel, ?Carbon $from): Collection
    {
        if ($from === null) {
            return collect();
        }

        $charged = PosSale::query()
            ->whereNotNull('member_id')
            ->whereColumn('amount_paid', '<', 'total')
            ->when($channel, fn (Builder $query) => $query->where('sale_channel', $channel->value))
            ->where('created_at', '<', $from)
            ->groupBy('member_id')
            ->selectRaw('member_id, SUM(total - amount_paid) as amount')
            ->pluck('amount', 'member_id');

        $paid = PosCreditPayment::query()
            ->when($channel, fn (Builder $query) => $query->where('sale_channel', $channel->value))
            ->where('created_at', '<', $from)
            ->groupBy('member_id')
            ->selectRaw('member_id, SUM(amount) as amount')
            ->pluck('amount', 'member_id');

        return $charged
            ->keys()
            ->filter(fn ($memberId): bool => abs(
                (float) $charged->get($memberId, 0) - (float) $paid->get($memberId, 0)
            ) > MemberPosCredit::EPSILON)
            ->values();
    }

    /**
     * @return Builder<PosSale>
     */
    private function chargesQuery(?PosSaleChannel $channel): Builder
    {
        return PosSale::query()
            ->where('member_id', $this->member->id)
            ->whereColumn('amount_paid', '<', 'total')
            ->when($channel, fn (Builder $query) => $query->where('sale_channel', $channel->value));
    }

    /**
     * @return Builder<PosCreditPayment>
     */
    private function paymentsQuery(?PosSaleChannel $channel): Builder
    {
        return PosCreditPayment::query()
            ->where('member_id', $this->member->id)
            ->when($channel, fn (Builder $query) => $query->where('sale_channel', $channel->value));
    }
}
