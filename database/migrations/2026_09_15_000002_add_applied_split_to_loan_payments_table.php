<?php

use App\Support\CharacterLoanLedgerEntries;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_payments', function (Blueprint $table): void {
            $table->decimal('interest_applied', 15, 2)->nullable()->after('kind');
            $table->decimal('principal_applied', 15, 2)->nullable()->after('interest_applied');
        });

        $payments = DB::table('loan_payments')
            ->whereNotNull('regular_loan_id')
            ->orderBy('regular_loan_id')
            ->orderBy('received_at')
            ->orderBy('id')
            ->get();

        $groups = $payments->groupBy(fn ($payment): string => $payment->regular_loan_id.'|'.(string) $payment->received_at);

        foreach ($groups as $rows) {
            if ($rows->count() !== 2) {
                continue;
            }

            $interest = $rows->first(
                fn ($row): bool => strcasecmp((string) $row->kind, CharacterLoanLedgerEntries::KIND_INTEREST) === 0
            );
            $principal = $rows->first(
                fn ($row): bool => strcasecmp((string) $row->kind, CharacterLoanLedgerEntries::KIND_PRINCIPAL) === 0
            );

            if ($interest === null || $principal === null) {
                continue;
            }

            DB::table('loan_payments')->where('id', $principal->id)->update([
                'amount' => round((float) $interest->amount + (float) $principal->amount, 2),
                'interest_applied' => round((float) $interest->amount, 2),
                'principal_applied' => round((float) $principal->amount, 2),
                'kind' => CharacterLoanLedgerEntries::KIND_PRINCIPAL,
            ]);

            DB::table('loan_payments')->where('id', $interest->id)->delete();
        }
    }

    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table): void {
            $table->dropColumn(['interest_applied', 'principal_applied']);
        });
    }
};
