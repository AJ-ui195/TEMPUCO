<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loan_payments') || ! Schema::hasColumn('loan_payments', 'invoice_no')) {
            return;
        }

        Schema::table('loan_payments', function (Blueprint $table): void {
            $table->dropColumn('invoice_no');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('loan_payments') || Schema::hasColumn('loan_payments', 'invoice_no')) {
            return;
        }

        Schema::table('loan_payments', function (Blueprint $table): void {
            $table->string('invoice_no', 64)->nullable()->after('official_receipt_no');
        });
    }
};
