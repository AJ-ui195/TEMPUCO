<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $loans = DB::table('loans')->get();

        foreach ($loans as $loan) {
            $notes = $loan->application_notes;

            if (filled($loan->loan_amount_words)) {
                $line = 'Amount in words: '.$loan->loan_amount_words;
                $notes = filled($notes) ? $notes.PHP_EOL.PHP_EOL.$line : $line;
            }

            if (filled($loan->applicant_date_of_birth)) {
                $line = 'Date of birth (at application): '.$loan->applicant_date_of_birth;
                $notes = filled($notes) ? $notes.PHP_EOL.$line : $line;
            }

            if ($notes !== $loan->application_notes) {
                DB::table('loans')->where('id', $loan->id)->update([
                    'application_notes' => $notes,
                ]);
            }
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'loan_amount_words',
                'applicant_signature_name',
                'applicant_date_of_birth',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('loan_amount_words')->nullable()->after('loan_amount');
            $table->string('applicant_signature_name')->nullable()->after('applicant_signed_at');
            $table->date('applicant_date_of_birth')->nullable()->after('applicant_signature_name');
        });
    }
};
