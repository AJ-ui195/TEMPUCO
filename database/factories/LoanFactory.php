<?php

namespace Database\Factories;

use App\Enums\LoanCategory;
use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use App\Models\Member;
use App\Models\RegularLoan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegularLoan>
 */
class LoanFactory extends Factory
{
    protected $model = RegularLoan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_category' => LoanCategory::AdditionalNew,
            'loan_type' => 'Personal loan',
            'loan_amount' => 15000,
            'loan_period_months' => 6,
            'installment_amount' => 2650,
            'first_payment_due_date' => now()->addWeeks(1)->toDateString(),
            'purpose_of_loan' => LoanPurpose::Personal,
            'mode_of_payment' => ModeOfPayment::CashPayment,
            'applicant_signed_at' => now()->toDateString(),
            'loan_date' => now()->toDateString(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (RegularLoan $loan): void {
            if ($loan->member_id === null) {
                $loan->member_id = Member::factory()->create()->id;
            }

            if ($loan->status === null) {
                $loan->status = LoanStatus::Pending;
            }
        });
    }

    public function awaitingVerification(): static
    {
        return $this->afterMaking(function (RegularLoan $loan): void {
            $loan->status = LoanStatus::AwaitingVerification;
            $loan->email_verification_token = hash('sha256', 'test-token');
            $loan->email_verified_at = null;
            $loan->approved_at = null;
            $loan->rejected_at = null;
        });
    }

    public function pending(): static
    {
        return $this->afterMaking(function (RegularLoan $loan): void {
            $loan->status = LoanStatus::Pending;
            $loan->email_verified_at = now();
            $loan->approved_at = null;
            $loan->rejected_at = null;
        });
    }

    public function approved(): static
    {
        return $this->afterMaking(function (RegularLoan $loan): void {
            $loan->status = LoanStatus::Approved;
            $loan->approved_at = now();
        });
    }

    public function rejected(): static
    {
        return $this->afterMaking(function (RegularLoan $loan): void {
            $loan->status = LoanStatus::Rejected;
            $loan->rejected_at = now();
            $loan->approved_at = null;
        });
    }
}
