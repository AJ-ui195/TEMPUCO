<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'has_email_authentication')) {
                $table->boolean('has_email_authentication')->default(false)->after('must_change_password');
            }

            if (! Schema::hasColumn('users', 'email_mfa_verified_until')) {
                $table->timestamp('email_mfa_verified_until')->nullable()->after('has_email_authentication');
            }
        });

        if (Schema::hasColumn('users', 'has_email_authentication')) {
            DB::table('users')
                ->where('role', UserRole::Admin->value)
                ->update(['has_email_authentication' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_mfa_verified_until')) {
                $table->dropColumn('email_mfa_verified_until');
            }

            if (Schema::hasColumn('users', 'has_email_authentication')) {
                $table->dropColumn('has_email_authentication');
            }
        });
    }
};
