<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'email_verification_token')) {
                $table->string('email_verification_token', 64)->nullable()->after('email_verified_at');
            }
        });

        DB::table('members')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'email_verification_token')) {
                $table->dropColumn('email_verification_token');
            }
        });
    }
};
