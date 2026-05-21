<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('loan_category', 32)->nullable()->after('status');
            $table->string('applicant_name')->nullable()->after('loan_category');
            $table->text('applicant_address')->nullable()->after('applicant_name');
            $table->string('loan_type', 255)->nullable()->after('apply_loan');
            $table->string('loan_amount_words')->nullable()->after('loan_amount');
            $table->date('first_payment_due_date')->nullable()->after('loan_period_months');
            $table->text('loan_purpose')->nullable()->after('first_payment_due_date');
            $table->date('applicant_signed_at')->nullable()->after('loan_purpose');
            $table->string('applicant_signature_name')->nullable()->after('applicant_signed_at');

            $table->string('cert_borrower_name')->nullable();
            $table->decimal('cert_fixed_savings_deposits', 15, 2)->nullable();
            $table->decimal('cert_standing_loan', 15, 2)->nullable();
            $table->date('cert_date_of_birth')->nullable();
            $table->text('cert_home_address')->nullable();
            $table->date('cert_treasurer_signed_at')->nullable();

            $table->date('committee_meeting_date')->nullable();
            $table->text('committee_conditions_notes')->nullable();
            $table->decimal('committee_approved_amount', 15, 2)->nullable();
            $table->date('committee_minutes_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'loan_category',
                'applicant_name',
                'applicant_address',
                'loan_type',
                'loan_amount_words',
                'first_payment_due_date',
                'loan_purpose',
                'applicant_signed_at',
                'applicant_signature_name',
                'cert_borrower_name',
                'cert_fixed_savings_deposits',
                'cert_standing_loan',
                'cert_date_of_birth',
                'cert_home_address',
                'cert_treasurer_signed_at',
                'committee_meeting_date',
                'committee_conditions_notes',
                'committee_approved_amount',
                'committee_minutes_date',
            ]);
        });
    }
};
