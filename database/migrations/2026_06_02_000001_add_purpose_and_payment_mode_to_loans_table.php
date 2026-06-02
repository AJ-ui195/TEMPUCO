<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('purpose_of_loan', 32)->nullable()->after('loan_purpose');
            $table->text('purpose_of_loan_other')->nullable()->after('purpose_of_loan');
            $table->string('mode_of_payment', 32)->nullable()->after('purpose_of_loan_other');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'purpose_of_loan',
                'purpose_of_loan_other',
                'mode_of_payment',
            ]);
        });
    }
};
