<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_branch_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_branch_id')->constrained('pos_branches')->cascadeOnDelete();
            $table->foreignId('pos_inventory_item_id')->constrained('pos_inventory_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['pos_branch_id', 'pos_inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_branch_inventory');
    }
};
