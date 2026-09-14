<?php

namespace App\Models;

use App\Enums\LoanCategory;
use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use App\Models\Concerns\IsMemberLoan;
use App\Models\Contracts\MemberLoan;
use Illuminate\Database\Eloquent\Model;

class RegularLoan extends Model implements MemberLoan
{
    use IsMemberLoan;

    protected $fillable = [
        'member_id',
        'status',
        'loan_category',
        'loan_type',
        'loan_amount',
        'loan_period_months',
        'installment_amount',
        'first_payment_due_date',
        'purpose_of_loan',
        'purpose_of_loan_other',
        'application_notes',
        'mode_of_payment',
        'applicant_signed_at',
        'loan_date',
        'approved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'loan_category' => LoanCategory::class,
            'purpose_of_loan' => LoanPurpose::class,
            'mode_of_payment' => ModeOfPayment::class,
            'loan_amount' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'first_payment_due_date' => 'date',
            'applicant_signed_at' => 'date',
            'loan_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function printType(): string
    {
        return 'regular';
    }
}
