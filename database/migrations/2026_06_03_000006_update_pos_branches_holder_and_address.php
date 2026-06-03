<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_branches', function (Blueprint $table) {
            $table->text('address')->nullable()->change();
            $table->renameColumn('manager_name', 'branch_holder');
        });
    }

    public function down(): void
    {
        Schema::table('pos_branches', function (Blueprint $table) {
            $table->renameColumn('branch_holder', 'manager_name');
            $table->text('address')->nullable(false)->change();
        });
    }
};
