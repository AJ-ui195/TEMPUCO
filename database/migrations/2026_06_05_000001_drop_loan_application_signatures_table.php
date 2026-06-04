<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->date('applicant_signed_at')->nullable()->after('mode_of_payment');
            $table->string('applicant_signature_name')->nullable()->after('applicant_signed_at');
            $table->date('applicant_date_of_birth')->nullable()->after('applicant_signature_name');
        });

        if (Schema::hasTable('loan_application_signatures')) {
            $signatures = DB::table('loan_application_signatures')->get();

            foreach ($signatures as $signature) {
                DB::table('loans')
                    ->where('id', $signature->loan_id)
                    ->update([
                        'applicant_signed_at' => $signature->signed_at,
                        'applicant_signature_name' => $signature->signature_name,
                        'applicant_date_of_birth' => $signature->date_of_birth,
                    ]);
            }

            Schema::dropIfExists('loan_application_signatures');
        }
    }

    public function down(): void
    {
        Schema::create('loan_application_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('signed_at')->nullable();
            $table->string('signature_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->timestamps();
        });

        $loans = DB::table('loans')
            ->whereNotNull('applicant_signed_at')
            ->orWhereNotNull('applicant_signature_name')
            ->orWhereNotNull('applicant_date_of_birth')
            ->get();

        foreach ($loans as $loan) {
            if (
                filled($loan->applicant_signed_at)
                || filled($loan->applicant_signature_name)
                || filled($loan->applicant_date_of_birth)
            ) {
                DB::table('loan_application_signatures')->insert([
                    'loan_id' => $loan->id,
                    'signed_at' => $loan->applicant_signed_at,
                    'signature_name' => $loan->applicant_signature_name,
                    'date_of_birth' => $loan->applicant_date_of_birth,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'applicant_signed_at',
                'applicant_signature_name',
                'applicant_date_of_birth',
            ]);
        });
    }
};
