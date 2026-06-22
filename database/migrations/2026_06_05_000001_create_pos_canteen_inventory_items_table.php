<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_canteen_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('pos_suppliers')
                ->nullOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->unsignedInteger('reorder_level')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_canteen_inventory_items');
    }
};
