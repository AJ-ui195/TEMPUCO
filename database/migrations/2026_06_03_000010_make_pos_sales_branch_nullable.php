<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropForeign(['pos_branch_id']);
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('pos_branch_id')->nullable()->change();
            $table->foreign('pos_branch_id')->references('id')->on('pos_branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropForeign(['pos_branch_id']);
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('pos_branch_id')->nullable(false)->change();
            $table->foreign('pos_branch_id')->references('id')->on('pos_branches')->cascadeOnDelete();
        });
    }
};
