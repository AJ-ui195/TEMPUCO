<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_application_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('signed_at')->nullable();
            $table->string('signature_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('borrower_name')->nullable();
            $table->decimal('fixed_savings_deposits', 15, 2)->nullable();
            $table->decimal('standing_loan', 15, 2)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('home_address')->nullable();
            $table->date('treasurer_signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_committee_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('meeting_date')->nullable();
            $table->text('conditions_notes')->nullable();
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->date('minutes_date')->nullable();
            $table->timestamps();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->text('application_notes')->nullable()->after('purpose_of_loan_other');
        });

        $loans = DB::table('loans')->get();

        foreach ($loans as $loan) {
            if (
                filled($loan->applicant_signed_at)
                || filled($loan->applicant_signature_name)
                || filled($loan->cert_date_of_birth)
            ) {
                DB::table('loan_application_signatures')->insert([
                    'loan_id' => $loan->id,
                    'signed_at' => $loan->applicant_signed_at,
                    'signature_name' => $loan->applicant_signature_name,
                    'date_of_birth' => $loan->cert_date_of_birth,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (
                filled($loan->cert_borrower_name)
                || filled($loan->cert_fixed_savings_deposits)
                || filled($loan->cert_standing_loan)
                || filled($loan->cert_date_of_birth)
                || filled($loan->cert_home_address)
                || filled($loan->cert_treasurer_signed_at)
            ) {
                DB::table('loan_certifications')->insert([
                    'loan_id' => $loan->id,
                    'borrower_name' => $loan->cert_borrower_name,
                    'fixed_savings_deposits' => $loan->cert_fixed_savings_deposits,
                    'standing_loan' => $loan->cert_standing_loan,
                    'date_of_birth' => $loan->cert_date_of_birth,
                    'home_address' => $loan->cert_home_address,
                    'treasurer_signed_at' => $loan->cert_treasurer_signed_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (
                filled($loan->committee_meeting_date)
                || filled($loan->committee_conditions_notes)
                || filled($loan->committee_approved_amount)
                || filled($loan->committee_minutes_date)
            ) {
                DB::table('loan_committee_decisions')->insert([
                    'loan_id' => $loan->id,
                    'meeting_date' => $loan->committee_meeting_date,
                    'conditions_notes' => $loan->committee_conditions_notes,
                    'approved_amount' => $loan->committee_approved_amount,
                    'minutes_date' => $loan->committee_minutes_date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $loanType = $loan->loan_type ?? $loan->apply_loan;

            DB::table('loans')
                ->where('id', $loan->id)
                ->update([
                    'loan_type' => $loanType,
                    'application_notes' => $loan->loan_purpose,
                ]);
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'applicant_name',
                'applicant_address',
                'apply_loan',
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

        if (Schema::hasColumn('pos_sales', 'subtotal')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                $table->dropColumn('subtotal');
            });
        }

        if (Schema::hasColumn('pos_sale_items', 'product_name')) {
            Schema::table('pos_sale_items', function (Blueprint $table) {
                $table->dropColumn(['product_name', 'sku']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('applicant_name')->nullable();
            $table->text('applicant_address')->nullable();
            $table->string('apply_loan', 255)->nullable();
            $table->text('loan_purpose')->nullable();
            $table->date('applicant_signed_at')->nullable();
            $table->string('applicant_signature_name')->nullable();
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

        $loans = DB::table('loans')->get();

        foreach ($loans as $loan) {
            $signature = DB::table('loan_application_signatures')->where('loan_id', $loan->id)->first();
            $certification = DB::table('loan_certifications')->where('loan_id', $loan->id)->first();
            $committee = DB::table('loan_committee_decisions')->where('loan_id', $loan->id)->first();

            DB::table('loans')->where('id', $loan->id)->update([
                'apply_loan' => $loan->loan_type,
                'loan_purpose' => $loan->application_notes,
                'applicant_signed_at' => $signature?->signed_at,
                'applicant_signature_name' => $signature?->signature_name,
                'cert_date_of_birth' => $signature?->date_of_birth ?? $certification?->date_of_birth,
                'cert_borrower_name' => $certification?->borrower_name,
                'cert_fixed_savings_deposits' => $certification?->fixed_savings_deposits,
                'cert_standing_loan' => $certification?->standing_loan,
                'cert_home_address' => $certification?->home_address,
                'cert_treasurer_signed_at' => $certification?->treasurer_signed_at,
                'committee_meeting_date' => $committee?->meeting_date,
                'committee_conditions_notes' => $committee?->conditions_notes,
                'committee_approved_amount' => $committee?->approved_amount,
                'committee_minutes_date' => $committee?->minutes_date,
            ]);
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('application_notes');
        });

        Schema::dropIfExists('loan_committee_decisions');
        Schema::dropIfExists('loan_certifications');
        Schema::dropIfExists('loan_application_signatures');

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->after('user_id');
        });

        DB::table('pos_sales')->update(['subtotal' => DB::raw('`total`')]);

        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->string('product_name')->after('pos_inventory_item_id');
            $table->string('sku')->nullable()->after('product_name');
        });
    }
};
