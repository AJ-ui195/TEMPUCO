<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pos_inventory_items')
            ->whereNull('sku')
            ->whereNotNull('barcode')
            ->orderBy('id')
            ->each(function (object $row): void {
                DB::table('pos_inventory_items')
                    ->where('id', $row->id)
                    ->update(['sku' => $row->barcode]);
            });

        Schema::table('pos_inventory_items', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });
    }

    public function down(): void
    {
        Schema::table('pos_inventory_items', function (Blueprint $table) {
            $table->string('barcode', 64)->nullable()->unique()->after('sku');
        });
    }
};
