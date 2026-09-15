<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (! Schema::hasColumn('loans', 'email_verification_token')) {
                $table->string('email_verification_token', 64)->nullable()->after('decision_notes');
            }

            if (! Schema::hasColumn('loans', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email_verification_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $drop = array_values(array_filter(
                ['email_verification_token', 'email_verified_at'],
                fn (string $column): bool => Schema::hasColumn('loans', $column),
            ));

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
