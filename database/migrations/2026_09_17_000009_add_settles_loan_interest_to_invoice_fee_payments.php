<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_fee_payments')
            || Schema::hasColumn('invoice_fee_payments', 'settles_loan_interest')) {
            return;
        }

        Schema::table('invoice_fee_payments', function (Blueprint $table) {
            $table->boolean('settles_loan_interest')->default(false)->after('interest');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoice_fee_payments')
            || ! Schema::hasColumn('invoice_fee_payments', 'settles_loan_interest')) {
            return;
        }

        Schema::table('invoice_fee_payments', function (Blueprint $table) {
            $table->dropColumn('settles_loan_interest');
        });
    }
};
