<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail of cash received from members to settle POS credit. Each
     * payment is allocated to the member's oldest unpaid sales, which is what
     * updates `pos_sales.amount_paid`; this table records who took the money.
     */
    public function up(): void
    {
        Schema::create('pos_credit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sale_channel', 16);
            $table->decimal('amount', 15, 2);
            $table->string('reference', 32)->unique();
            $table->timestamps();

            $table->index(['member_id', 'sale_channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_credit_payments');
    }
};
