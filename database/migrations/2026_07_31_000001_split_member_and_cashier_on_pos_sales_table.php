<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `user_id` carried two different meanings (member on credit sales, cashier
     * on cash sales), so a member could not be attached to a cash sale. Split it
     * into one column per fact.
     */
    public function up(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('pos_branch_id')->constrained('users')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->after('member_id')->constrained('users')->nullOnDelete();
        });

        $memberIds = DB::table('users')
            ->where('role', UserRole::User->value)
            ->pluck('id');

        DB::table('pos_sales')
            ->whereIn('user_id', $memberIds)
            ->update(['member_id' => DB::raw('user_id')]);

        DB::table('pos_sales')
            ->whereNotIn('user_id', $memberIds)
            ->update(['cashier_id' => DB::raw('user_id')]);

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('pos_branch_id')->constrained('users')->cascadeOnDelete();
        });

        DB::table('pos_sales')->update([
            'user_id' => DB::raw('COALESCE(member_id, cashier_id)'),
        ]);

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');
            $table->dropConstrainedForeignId('cashier_id');
        });
    }
};
