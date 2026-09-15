<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE loan_payments
                MODIFY principal_applied DECIMAL(15, 2) NULL AFTER character_loan_id,
                MODIFY interest_applied DECIMAL(15, 2) NULL AFTER principal_applied,
                MODIFY amount DECIMAL(15, 2) NOT NULL AFTER interest_applied,
                MODIFY kind VARCHAR(20) NOT NULL DEFAULT \'principal\' AFTER amount
        ');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE loan_payments
                MODIFY amount DECIMAL(15, 2) NOT NULL AFTER character_loan_id,
                MODIFY kind VARCHAR(20) NOT NULL DEFAULT \'principal\' AFTER amount,
                MODIFY interest_applied DECIMAL(15, 2) NULL AFTER kind,
                MODIFY principal_applied DECIMAL(15, 2) NULL AFTER interest_applied
        ');
    }
};
