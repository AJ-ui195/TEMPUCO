<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Credit settlements were being written back into `pos_sales.amount_paid`,
     * which made that column a running aggregate of the payments table and
     * retroactively changed historical sales reports. `amount_paid` is now the
     * immutable amount tendered at checkout, and outstanding balances are
     * derived from `pos_credit_payments`.
     *
     * Settlements were allocated oldest sale first, so they are unwound in the
     * same order.
     */
    public function up(): void
    {
        $paymentTotals = DB::table('pos_credit_payments')
            ->select('member_id', 'sale_channel', DB::raw('SUM(amount) as paid'))
            ->groupBy('member_id', 'sale_channel')
            ->get();

        foreach ($paymentTotals as $total) {
            $remaining = round((float) $total->paid, 2);

            $sales = DB::table('pos_sales')
                ->where('member_id', $total->member_id)
                ->where('sale_channel', $total->sale_channel)
                ->where('amount_paid', '>', 0)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'amount_paid']);

            foreach ($sales as $sale) {
                if ($remaining <= 0) {
                    break;
                }

                $reverted = min($remaining, (float) $sale->amount_paid);

                DB::table('pos_sales')
                    ->where('id', $sale->id)
                    ->update(['amount_paid' => round((float) $sale->amount_paid - $reverted, 2)]);

                $remaining = round($remaining - $reverted, 2);
            }
        }
    }

    public function down(): void
    {
        // Re-applying settlements to sales would restore the aggregate this
        // migration removed, so the payment rows are left as the record.
    }
};
