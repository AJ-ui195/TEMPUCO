<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('branch_number')->unique();
            $table->string('name');
            $table->string('code', 16)->unique();
            $table->text('address');
            $table->string('phone', 32)->nullable();
            $table->string('manager_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_branches');
    }
};
