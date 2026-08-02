<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A voided sale line. Quantity and amount are not copied here: they are
        // read from the pos_sale_items row this points at.
        Schema::create('pos_sale_item_voids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_item_id')->unique()->constrained('pos_sale_items')->cascadeOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sale_item_voids');
    }
};
