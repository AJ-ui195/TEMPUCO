<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Damaged stock pulled out of grocery inventory. Product details stay on
        // pos_inventory_items; only the pull-out facts live here.
        Schema::create('pos_inventory_damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_inventory_item_id')->constrained('pos_inventory_items')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('reason', 255);
            $table->timestamps();

            $table->index(['pos_inventory_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_inventory_damages');
    }
};
