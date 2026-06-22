<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sale_items', function (Blueprint $table) {
            // Each line references one catalog via FK (grocery or canteen, never both).
            // unit_price and line_total remain intentional audit snapshots per 3NF rules.
            $table->foreignId('pos_inventory_item_id')->nullable()->change();

            $table->foreignId('pos_canteen_inventory_item_id')
                ->nullable()
                ->after('pos_inventory_item_id')
                ->constrained('pos_canteen_inventory_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pos_canteen_inventory_item_id');
        });

        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->foreignId('pos_inventory_item_id')->nullable(false)->change();
        });
    }
};
