<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_payments', function (Blueprint $table): void {
            $table->string('official_receipt_no', 64)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table): void {
            $table->dropColumn('official_receipt_no');
        });
    }
};
