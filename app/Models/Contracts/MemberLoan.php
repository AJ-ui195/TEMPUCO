<?php

namespace App\Models\Contracts;

use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use App\Models\LoanCertification;
use App\Models\LoanCommitteeDecision;
use App\Models\Member;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $member_id
 * @property LoanStatus $status
 * @property string $loan_type
 * @property float|string $loan_amount
 * @property int $loan_period_months
 * @property float|string $installment_amount
 * @property Carbon|null $first_payment_due_date
 * @property LoanPurpose|null $purpose_of_loan
 * @property string|null $purpose_of_loan_other
 * @property string|null $application_notes
 * @property ModeOfPayment|null $mode_of_payment
 * @property Carbon|null $applicant_signed_at
 * @property Carbon|null $loan_date
 * @property Carbon|null $approved_at
 * @property Carbon|null $rejected_at
 * @property int|null $decided_by
 * @property string|null $decision_notes
 * @property string|null $email_verification_token
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $created_at
 * @property-read Member|null $member
 * @property-read Member|null $user
 * @property-read LoanCertification|null $certification
 * @property-read LoanCommitteeDecision|null $committeeDecision
 */
interface MemberLoan
{
    public function getKey();

    public function member(): BelongsTo;

    public function user(): BelongsTo;

    public function payments(): HasMany;

    public function certification(): HasOne;

    public function committeeDecision(): HasOne;

    public function amountInWords(): string;

    public function printType(): string;

    public function loanForeignKey(): string;
}
