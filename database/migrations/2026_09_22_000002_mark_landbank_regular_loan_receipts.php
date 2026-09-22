<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('loan_payments')
            ->whereNotNull('regular_loan_id')
            ->whereNotNull('official_receipt_no')
            ->where(function ($query): void {
                $query->whereNull('receipt_kind')
                    ->orWhere('receipt_kind', 'official_receipt');
            })
            ->whereTime('received_at', '00:00:00')
            ->update(['receipt_kind' => 'landbank']);

        $sharedNumbers = DB::table('loan_payments')
            ->whereNotNull('regular_loan_id')
            ->whereNotNull('official_receipt_no')
            ->select('official_receipt_no')
            ->groupBy('official_receipt_no')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('official_receipt_no');

        if ($sharedNumbers->isNotEmpty()) {
            DB::table('loan_payments')
                ->whereNotNull('regular_loan_id')
                ->whereIn('official_receipt_no', $sharedNumbers)
                ->where(function ($query): void {
                    $query->whereNull('receipt_kind')
                        ->orWhere('receipt_kind', 'official_receipt');
                })
                ->update(['receipt_kind' => 'landbank']);
        }
    }

    public function down(): void
    {
        DB::table('loan_payments')
            ->where('receipt_kind', 'landbank')
            ->update(['receipt_kind' => 'official_receipt']);
    }
};
