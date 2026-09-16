<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * POS checkout stores members.id on member_id, but FKs still pointed at users
 * and PosSale resolved User — receipts showed the wrong person when IDs overlapped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        $this->retargetMemberForeignKey('pos_sales', nullOnDelete: true);
        $this->retargetMemberForeignKey('pos_credit_payments', nullOnDelete: false);
    }

    public function down(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        $this->restoreUserForeignKey('pos_sales', nullOnDelete: true);
        $this->restoreUserForeignKey('pos_credit_payments', nullOnDelete: false);
    }

    private function retargetMemberForeignKey(string $table, bool $nullOnDelete): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'member_id')) {
            return;
        }

        $foreign = collect(Schema::getForeignKeys($table))
            ->first(fn (array $key): bool => in_array('member_id', $key['columns'], true));

        if ($foreign !== null && ($foreign['foreign_table'] ?? null) === 'members') {
            return;
        }

        if ($foreign !== null) {
            Schema::table($table, function (Blueprint $blueprint) use ($foreign): void {
                $blueprint->dropForeign($foreign['name']);
            });
        }

        $this->remapMemberIdsFromLegacyUsers($table);

        Schema::table($table, function (Blueprint $blueprint) use ($nullOnDelete): void {
            $foreign = $blueprint->foreign('member_id')->references('id')->on('members');

            if ($nullOnDelete) {
                $foreign->nullOnDelete();
            } else {
                $foreign->cascadeOnDelete();
            }
        });
    }

    private function restoreUserForeignKey(string $table, bool $nullOnDelete): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'member_id')) {
            return;
        }

        $foreign = collect(Schema::getForeignKeys($table))
            ->first(fn (array $key): bool => in_array('member_id', $key['columns'], true));

        if ($foreign !== null) {
            Schema::table($table, function (Blueprint $blueprint) use ($foreign): void {
                $blueprint->dropForeign($foreign['name']);
            });
        }

        Schema::table($table, function (Blueprint $blueprint) use ($nullOnDelete): void {
            $foreign = $blueprint->foreign('member_id')->references('id')->on('users');

            if ($nullOnDelete) {
                $foreign->nullOnDelete();
            } else {
                $foreign->cascadeOnDelete();
            }
        });
    }

    /**
     * Leave rows that already point at members.id. Remap leftover user ids by email.
     */
    private function remapMemberIdsFromLegacyUsers(string $table): void
    {
        $existingMemberIdSet = array_fill_keys(
            array_map('intval', DB::table('members')->pluck('id')->all()),
            true,
        );

        $memberIdByEmail = DB::table('members')->pluck('id', 'email');
        $legacyEmailByUserId = DB::table('users')
            ->where('role', 'user')
            ->pluck('email', 'id');

        foreach (DB::table($table)->whereNotNull('member_id')->get(['id', 'member_id']) as $row) {
            $memberId = (int) $row->member_id;

            if (isset($existingMemberIdSet[$memberId])) {
                continue;
            }

            $legacyEmail = $legacyEmailByUserId[$memberId] ?? null;
            $resolvedMemberId = $legacyEmail !== null
                ? ($memberIdByEmail[$legacyEmail] ?? null)
                : null;

            if ($resolvedMemberId === null) {
                // Keep nullable sales clear; leave non-nullable credit rows for manual fix.
                if ($table === 'pos_sales') {
                    DB::table($table)->where('id', $row->id)->update(['member_id' => null]);
                }

                continue;
            }

            DB::table($table)->where('id', $row->id)->update([
                'member_id' => $resolvedMemberId,
            ]);
        }
    }
};
