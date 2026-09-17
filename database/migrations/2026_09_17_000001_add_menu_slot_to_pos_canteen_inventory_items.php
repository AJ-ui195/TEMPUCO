<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_canteen_inventory_items')) {
            return;
        }

        Schema::table('pos_canteen_inventory_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('pos_canteen_inventory_items', 'menu_slot')) {
                $table->unsignedTinyInteger('menu_slot')->nullable()->unique()->after('is_active');
            }
        });

        $defaults = [
            1 => 'Rice meal',
            2 => 'Noodles',
            3 => 'Snack',
            4 => 'Drink',
            5 => 'Combo',
        ];

        foreach ($defaults as $slot => $name) {
            $exists = DB::table('pos_canteen_inventory_items')
                ->where('menu_slot', $slot)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('pos_canteen_inventory_items')->insert([
                'name' => $name,
                'sku' => 'CANTEEN-MENU-'.$slot,
                'quantity' => 0,
                'unit_price' => 0,
                'cost' => 0,
                'is_active' => true,
                'menu_slot' => $slot,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('pos_canteen_inventory_items')) {
            return;
        }

        if (Schema::hasColumn('pos_canteen_inventory_items', 'menu_slot')) {
            Schema::table('pos_canteen_inventory_items', function (Blueprint $table): void {
                $table->dropUnique(['menu_slot']);
                $table->dropColumn('menu_slot');
            });
        }
    }
};
