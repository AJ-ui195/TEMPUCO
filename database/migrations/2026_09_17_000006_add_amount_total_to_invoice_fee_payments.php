<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_fee_payments', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->default(0)->after('others');
        });

        DB::table('invoice_fee_payments')->orderBy('id')->each(function (object $row): void {
            DB::table('invoice_fee_payments')->where('id', $row->id)->update([
                'amount' => round(
                    (float) $row->interest
                    + (float) $row->surcharge
                    + (float) $row->membership_fee
                    + (float) $row->others,
                    2,
                ),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('invoice_fee_payments', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
};
