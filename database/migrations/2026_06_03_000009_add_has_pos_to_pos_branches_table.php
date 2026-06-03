<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_branches', function (Blueprint $table) {
            $table->boolean('has_pos')->default(true)->after('is_active');
        });

        DB::table('pos_branches')
            ->where('branch_number', 1)
            ->update(['has_pos' => false]);
    }

    public function down(): void
    {
        Schema::table('pos_branches', function (Blueprint $table) {
            $table->dropColumn('has_pos');
        });
    }
};
