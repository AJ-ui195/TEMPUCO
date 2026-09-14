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
            if (! Schema::hasColumn('members', 'password')) {
                $table->string('password')->nullable()->after('email');
            }

            if (! Schema::hasColumn('members', 'points')) {
                $table->unsignedInteger('points')->default(0)->after('is_retiree');
            }

            if (! Schema::hasColumn('members', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('points')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        $members = DB::table('members')->get();

        foreach ($members as $member) {
            $creatorId = null;

            if (Schema::hasColumn('members', 'username') && filled($member->username ?? null)) {
                $creatorId = DB::table('users')
                    ->where('name', $member->username)
                    ->where('role', '!=', 'user')
                    ->value('id');
            }

            $user = DB::table('users')->where('id', $member->user_id)->first();

            DB::table('members')->where('id', $member->id)->update([
                'created_by' => $creatorId,
                'password' => $member->password ?? $user?->password,
                'points' => (int) ($user->points ?? 0),
            ]);
        }

        if (Schema::hasColumn('members', 'username')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $drop = [];

            foreach (['address', 'cellphone', 'date_of_birth', 'is_retiree', 'points'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $drop[] = $column;
                }
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'cellphone')) {
                $table->string('cellphone', 32)->nullable()->after('address');
            }
            if (! Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('cellphone');
            }
            if (! Schema::hasColumn('users', 'is_retiree')) {
                $table->boolean('is_retiree')->default(false)->after('date_of_birth');
            }
            if (! Schema::hasColumn('users', 'points')) {
                $table->unsignedInteger('points')->default(0)->after('is_retiree');
            }
        });

        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'username')) {
                $table->string('username')->nullable()->after('user_id');
            }
        });

        $members = DB::table('members')->get();

        foreach ($members as $member) {
            $creatorName = $member->created_by
                ? DB::table('users')->where('id', $member->created_by)->value('name')
                : null;

            DB::table('members')->where('id', $member->id)->update([
                'username' => $creatorName ?: 'unknown',
            ]);

            DB::table('users')->where('id', $member->user_id)->update([
                'points' => (int) ($member->points ?? 0),
                'address' => $member->address,
                'cellphone' => $member->contact_number,
                'date_of_birth' => $member->date_of_birth,
                'is_retiree' => (bool) ($member->is_retiree ?? false),
            ]);
        }

        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('members', 'password')) {
                $table->dropColumn('password');
            }
            if (Schema::hasColumn('members', 'points')) {
                $table->dropColumn('points');
            }
        });
    }
};
