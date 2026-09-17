<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_canteen_inventory_items')) {
            return;
        }

        Schema::table('pos_canteen_inventory_items', function (Blueprint $table): void {
            if (Schema::hasColumn('pos_canteen_inventory_items', 'supplier_id')) {
                $table->dropConstrainedForeignId('supplier_id');
            }

            $drop = array_values(array_filter(
                ['description', 'reorder_level'],
                fn (string $column): bool => Schema::hasColumn('pos_canteen_inventory_items', $column),
            ));

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pos_canteen_inventory_items')) {
            return;
        }

        Schema::table('pos_canteen_inventory_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('pos_canteen_inventory_items', 'description')) {
                $table->text('description')->nullable()->after('sku');
            }

            if (! Schema::hasColumn('pos_canteen_inventory_items', 'supplier_id')) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('description')
                    ->constrained('pos_suppliers')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('pos_canteen_inventory_items', 'reorder_level')) {
                $table->unsignedInteger('reorder_level')->nullable()->after('cost');
            }
        });
    }
};
