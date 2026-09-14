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
            if (! Schema::hasColumn('members', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
            if (! Schema::hasColumn('members', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
        });

        $userToMember = DB::table('members')
            ->whereNotNull('user_id')
            ->pluck('id', 'user_id'); // [old_users.id => members.id]

        // Remap loans.user_id → members.id
        foreach ($userToMember as $userId => $memberId) {
            DB::table('loans')->where('user_id', $userId)->update(['user_id' => $memberId]);
        }

        // Remap POS FKs → members.id
        foreach ($userToMember as $userId => $memberId) {
            DB::table('pos_sales')->where('member_id', $userId)->update(['member_id' => $memberId]);
            if (Schema::hasTable('pos_credit_payments')) {
                DB::table('pos_credit_payments')->where('member_id', $userId)->update(['member_id' => $memberId]);
            }
        }

        // Point FKs at members instead of users
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('loans', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('members')->cascadeOnDelete();
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->foreign('member_id')->references('id')->on('members')->nullOnDelete();
        });

        if (Schema::hasTable('pos_credit_payments')) {
            Schema::table('pos_credit_payments', function (Blueprint $table) {
                $table->dropForeign(['member_id']);
            });
            Schema::table('pos_credit_payments', function (Blueprint $table) {
                $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            });
        }

        // Drop members.user_id (login link no longer used)
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // Remove member login rows from users
        $oldUserIds = $userToMember->keys()->all();
        if ($oldUserIds !== []) {
            DB::table('users')->whereIn('id', $oldUserIds)->delete();
        }
        DB::table('users')->where('role', 'user')->delete();
    }

    public function down(): void
    {
        // Irreversible data remap — recreate nullable user_id only.
        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }
};
