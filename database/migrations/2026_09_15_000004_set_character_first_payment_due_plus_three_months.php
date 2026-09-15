<?php

use App\Models\CharacterLoan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        CharacterLoan::query()->each(function (CharacterLoan $loan): void {
            $start = $loan->loan_date ?? $loan->applicant_signed_at ?? $loan->approved_at;

            if ($start === null) {
                return;
            }

            $loan->first_payment_due_date = Carbon::parse($start)->addMonthsNoOverflow(3)->toDateString();
            $loan->saveQuietly();
        });
    }

    public function down(): void
    {
        //
    }
};
