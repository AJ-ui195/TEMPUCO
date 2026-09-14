<?php

namespace App\Models\Contracts;

use App\Models\LoanCertification;
use App\Models\LoanCommitteeDecision;
use App\Models\Member;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $member_id
 * @property \App\Enums\LoanStatus $status
 * @property string $loan_type
 * @property float|string $loan_amount
 * @property int $loan_period_months
 * @property float|string $installment_amount
 * @property \Illuminate\Support\Carbon|null $first_payment_due_date
 * @property \App\Enums\LoanPurpose|null $purpose_of_loan
 * @property string|null $purpose_of_loan_other
 * @property string|null $application_notes
 * @property \App\Enums\ModeOfPayment|null $mode_of_payment
 * @property \Illuminate\Support\Carbon|null $applicant_signed_at
 * @property \Illuminate\Support\Carbon|null $loan_date
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
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
