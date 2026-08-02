<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_inventory_items', function (Blueprint $table) {
            $table->date('expiration_date')->nullable()->after('reorder_level');
        });

        Schema::table('pos_branch_inventory', function (Blueprint $table) {
            $table->date('expiration_date')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('pos_inventory_items', function (Blueprint $table) {
            $table->dropColumn('expiration_date');
        });

        Schema::table('pos_branch_inventory', function (Blueprint $table) {
            $table->dropColumn('expiration_date');
        });
    }
};
