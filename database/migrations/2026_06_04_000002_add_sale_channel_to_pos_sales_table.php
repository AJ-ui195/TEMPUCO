<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->string('sale_channel', 16)->nullable()->after('user_id');
        });

        DB::table('pos_sales')
            ->whereNull('sale_channel')
            ->update(['sale_channel' => 'grocery']);
    }

    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropColumn('sale_channel');
        });
    }
};
