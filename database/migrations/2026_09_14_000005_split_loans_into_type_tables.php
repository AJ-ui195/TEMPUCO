<?php

use App\Models\CharacterLoan;
use App\Models\QuickLoan;
use App\Models\RegularLoan;
use App\Support\LoanTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regular_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('status');
            $table->string('loan_category')->nullable();
            $table->string('loan_type');
            $table->decimal('loan_amount', 15, 2);
            $table->unsignedSmallInteger('loan_period_months');
            $table->decimal('installment_amount', 15, 2);
            $table->date('first_payment_due_date')->nullable();
            $table->string('purpose_of_loan')->nullable();
            $table->text('purpose_of_loan_other')->nullable();
            $table->text('application_notes')->nullable();
            $table->string('mode_of_payment')->nullable();
            $table->date('applicant_signed_at')->nullable();
            $table->date('loan_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_notes')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('email_verification_token', 64)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quick_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('status');
            $table->string('loan_type')->default(LoanTypes::QUICK);
            $table->decimal('loan_amount', 15, 2);
            $table->unsignedSmallInteger('loan_period_months')->default(1);
            $table->decimal('installment_amount', 15, 2);
            $table->date('first_payment_due_date')->nullable();
            $table->string('purpose_of_loan')->nullable();
            $table->text('purpose_of_loan_other')->nullable();
            $table->text('application_notes')->nullable();
            $table->string('mode_of_payment')->nullable();
            $table->date('applicant_signed_at')->nullable();
            $table->date('loan_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_notes')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('email_verification_token', 64)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('character_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('status');
            $table->string('loan_type')->default(LoanTypes::CHARACTER);
            $table->decimal('loan_amount', 15, 2);
            $table->unsignedSmallInteger('loan_period_months');
            $table->decimal('installment_amount', 15, 2);
            $table->date('first_payment_due_date')->nullable();
            $table->string('purpose_of_loan')->nullable();
            $table->text('purpose_of_loan_other')->nullable();
            $table->text('application_notes')->nullable();
            $table->string('mode_of_payment')->nullable();
            $table->date('applicant_signed_at')->nullable();
            $table->date('loan_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_notes')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('email_verification_token', 64)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        $this->addMorphColumns('loan_payments');
        $this->addMorphColumns('loan_certifications');
        $this->addMorphColumns('loan_committee_decisions');

        if (Schema::hasTable('loans')) {
            $this->copyExistingLoans();
        }

        if (Schema::hasColumn('loan_payments', 'loan_id')) {
            $this->dropLoanId('loan_payments');
        }

        if (Schema::hasColumn('loan_certifications', 'loan_id')) {
            $this->dropLoanId('loan_certifications');
        }

        if (Schema::hasColumn('loan_committee_decisions', 'loan_id')) {
            $this->dropLoanId('loan_committee_decisions');
        }

        Schema::dropIfExists('loans');
    }

    public function down(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('members')->cascadeOnDelete();
            $table->string('status');
            $table->string('loan_category')->nullable();
            $table->string('loan_type');
            $table->decimal('loan_amount', 15, 2);
            $table->unsignedSmallInteger('loan_period_months');
            $table->decimal('installment_amount', 15, 2);
            $table->date('first_payment_due_date')->nullable();
            $table->string('purpose_of_loan')->nullable();
            $table->text('purpose_of_loan_other')->nullable();
            $table->text('application_notes')->nullable();
            $table->string('mode_of_payment')->nullable();
            $table->date('applicant_signed_at')->nullable();
            $table->date('loan_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('character_loans');
        Schema::dropIfExists('quick_loans');
        Schema::dropIfExists('regular_loans');
    }

    private function dropLoanId(string $table): void
    {
        if (! Schema::hasColumn($table, 'loan_id')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                foreach (Schema::getIndexes($table) as $index) {
                    $columns = $index['columns'] ?? [];
                    $name = $index['name'] ?? null;

                    if ($name === null || ($index['primary'] ?? false)) {
                        continue;
                    }

                    if ($columns === ['loan_id'] && ($index['unique'] ?? false)) {
                        $blueprint->dropUnique($name);
                    } elseif ($columns === ['loan_id']) {
                        $blueprint->dropIndex($name);
                    }
                }

                try {
                    $blueprint->dropForeign(['loan_id']);
                } catch (Throwable) {
                    // SQLite may not expose the foreign key name the same way.
                }
            });

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('loan_id');
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function addMorphColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            if (! Schema::hasColumn($table, 'loanable_type')) {
                $blueprint->nullableMorphs('loanable');
            }
        });
    }

    private function copyExistingLoans(): void
    {
        $loans = DB::table('loans')->orderBy('id')->get();

        foreach ($loans as $loan) {
            $type = strtoupper(trim((string) $loan->loan_type));
            $payload = [
                'member_id' => $loan->user_id,
                'status' => $loan->status,
                'loan_type' => $loan->loan_type,
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
            ];

            foreach (['decided_by', 'decision_notes', 'rejected_at', 'email_verification_token', 'email_verified_at'] as $column) {
                if (property_exists($loan, $column) || isset($loan->{$column})) {
                    $payload[$column] = $loan->{$column} ?? null;
                }
            }

            if ($type === LoanTypes::QUICK) {
                $newId = DB::table('quick_loans')->insertGetId($payload);
                $morphClass = QuickLoan::class;
            } elseif ($type === LoanTypes::CHARACTER) {
                $newId = DB::table('character_loans')->insertGetId($payload);
                $morphClass = CharacterLoan::class;
            } else {
                $payload['loan_category'] = $loan->loan_category;
                $newId = DB::table('regular_loans')->insertGetId($payload);
                $morphClass = RegularLoan::class;
            }

            $this->remapRelated('loan_payments', $loan->id, $morphClass, $newId);
            $this->remapRelated('loan_certifications', $loan->id, $morphClass, $newId);
            $this->remapRelated('loan_committee_decisions', $loan->id, $morphClass, $newId);
        }
    }

    private function remapRelated(string $table, int $oldLoanId, string $morphClass, int $newId): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'loan_id')) {
            return;
        }

        DB::table($table)
            ->where('loan_id', $oldLoanId)
            ->update([
                'loanable_type' => $morphClass,
                'loanable_id' => $newId,
            ]);
    }
};
