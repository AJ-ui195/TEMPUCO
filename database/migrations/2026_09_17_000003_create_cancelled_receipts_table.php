<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancelled_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 64);
            $table->string('kind', 32);
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kind', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancelled_receipts');
    }
};
