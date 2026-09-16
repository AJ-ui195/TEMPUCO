<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_fee_payments')
            || ! Schema::hasColumn('invoice_fee_payments', 'settles_loan_interest')) {
            return;
        }

        DB::table('invoice_fee_payments')
            ->where('interest', '>', 0)
            ->whereRaw('ABS(interest - amount) < 0.01')
            ->update(['settles_loan_interest' => true]);
    }

    public function down(): void
    {
        //
    }
};
