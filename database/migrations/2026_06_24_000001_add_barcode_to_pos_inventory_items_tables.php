<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_inventory_items', function (Blueprint $table) {
            $table->string('barcode', 64)->nullable()->unique()->after('sku');
        });

        Schema::table('pos_canteen_inventory_items', function (Blueprint $table) {
            $table->string('barcode', 64)->nullable()->unique()->after('sku');
        });
    }

    public function down(): void
    {
        Schema::table('pos_canteen_inventory_items', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });

        Schema::table('pos_inventory_items', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });
    }
};
