<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('invoice_no', 64);
            $table->string('category', 32);
            $table->decimal('amount', 15, 2);
            $table->string('collection_method', 16)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index('invoice_no');
            $table->index(['member_id', 'invoice_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_fee_payments');
    }
};
