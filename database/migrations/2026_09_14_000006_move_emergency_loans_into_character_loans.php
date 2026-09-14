<?php

use App\Models\CharacterLoan;
use App\Models\RegularLoan;
use App\Support\LoanTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('regular_loans') || ! Schema::hasTable('character_loans')) {
            return;
        }

        $loans = DB::table('regular_loans')
            ->whereRaw('UPPER(TRIM(loan_type)) = ?', [LoanTypes::EMERGENCY])
            ->orderBy('id')
            ->get();

        foreach ($loans as $loan) {
            $newId = DB::table('character_loans')->insertGetId([
                'member_id' => $loan->member_id,
                'status' => $loan->status,
                'loan_type' => LoanTypes::EMERGENCY,
                'loan_amount' => $loan->loan_amount,
                'loan_period_months' => $loan->loan_period_months,
                'installment_amount' => $loan->installment_amount,
                'first_payment_due_date' => $loan->first_payment_due_date,
                'purpose_of_loan' => $loan->purpose_of_loan,
                'purpose_of_loan_other' => $loan->purpose_of_loan_other,
                'application_notes' => $loan->application_notes ?? null,
                'mode_of_payment' => $loan->mode_of_payment,
                'applicant_signed_at' => $loan->applicant_signed_at,
                'loan_date' => $loan->loan_date,
                'approved_at' => $loan->approved_at,
                'created_at' => $loan->created_at,
                'updated_at' => $loan->updated_at,
            ]);

            foreach (['loan_payments', 'loan_certifications', 'loan_committee_decisions'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)
                    ->where('loanable_type', RegularLoan::class)
                    ->where('loanable_id', $loan->id)
                    ->update([
                        'loanable_type' => CharacterLoan::class,
                        'loanable_id' => $newId,
                    ]);
            }

            DB::table('regular_loans')->where('id', $loan->id)->delete();
        }
    }

    public function down(): void
    {
        // Irreversible: emergency rows belong on character_loans.
    }
};
