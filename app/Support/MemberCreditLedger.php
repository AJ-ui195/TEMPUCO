<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\PosCreditPayment;
use App\Models\PosSale;
use App\Models\User;
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
     * Charges and payments in the order they happened, with the balance owed
     * after each one.
     *
     * @return Collection<int, array{
     *     type: string,
     *     date: \Illuminate\Support\Carbon,
     *     reference: string,
     *     channel: ?PosSaleChannel,
     *     description: string,
     *     charge: float,
     *     payment: float,
     *     balance: float
     * }>
     */
    public function entries(?PosSaleChannel $channel = null): Collection
    {
        $charges = PosSale::query()
            ->where('member_id', $this->member->id)
            ->whereColumn('amount_paid', '<', 'total')
            ->when($channel, fn ($query) => $query->where('sale_channel', $channel->value))
            ->withSum('items as items_quantity', 'quantity')
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

        $payments = PosCreditPayment::query()
            ->where('member_id', $this->member->id)
            ->when($channel, fn ($query) => $query->where('sale_channel', $channel->value))
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

        $balance = 0.0;

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
     * @return array{charged: float, paid: float, balance: float, entry_count: int}
     */
    public function summary(?PosSaleChannel $channel = null): array
    {
        $entries = $this->entries($channel);

        $charged = round((float) $entries->sum('charge'), 2);
        $paid = round((float) $entries->sum('payment'), 2);

        return [
            'charged' => $charged,
            'paid' => $paid,
            'balance' => round(max(0, $charged - $paid), 2),
            'entry_count' => $entries->count(),
        ];
    }
}
