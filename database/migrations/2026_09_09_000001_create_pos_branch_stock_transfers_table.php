<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each warehouse-to-branch movement. Product details stay on
        // pos_inventory_items; only the transfer facts live here.
        Schema::create('pos_branch_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_branch_id')->constrained('pos_branches')->cascadeOnDelete();
            $table->foreignId('pos_inventory_item_id')->constrained('pos_inventory_items')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->date('expiration_date')->nullable();
            $table->timestamps();

            $table->index(['pos_branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_branch_stock_transfers');
    }
};
