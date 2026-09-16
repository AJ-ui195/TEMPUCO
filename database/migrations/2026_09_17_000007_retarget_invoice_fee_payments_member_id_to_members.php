<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_fee_payments') || ! Schema::hasTable('members')) {
            return;
        }

        $this->dropMemberForeign();

        Schema::table('invoice_fee_payments', function (Blueprint $table) {
            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoice_fee_payments')) {
            return;
        }

        $this->dropMemberForeign();

        Schema::table('invoice_fee_payments', function (Blueprint $table) {
            $table->foreign('member_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    private function dropMemberForeign(): void
    {
        $foreign = collect(Schema::getForeignKeys('invoice_fee_payments'))
            ->first(fn (array $key): bool => in_array('member_id', $key['columns'], true));

        if ($foreign === null) {
            return;
        }

        Schema::table('invoice_fee_payments', function (Blueprint $table) use ($foreign): void {
            $table->dropForeign($foreign['name']);
        });
    }
};
