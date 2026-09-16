<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_payments', function (Blueprint $table): void {
            $table->string('invoice_no', 64)->nullable()->after('official_receipt_no');
            $table->string('receipt_kind', 32)->nullable()->after('invoice_no');
        });

        Schema::table('pos_credit_payments', function (Blueprint $table): void {
            $table->string('invoice_no', 64)->nullable()->after('reference');
            $table->string('receipt_kind', 32)->nullable()->after('invoice_no');
        });
    }

    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table): void {
            $table->dropColumn(['invoice_no', 'receipt_kind']);
        });

        Schema::table('pos_credit_payments', function (Blueprint $table): void {
            $table->dropColumn(['invoice_no', 'receipt_kind']);
        });
    }
};
