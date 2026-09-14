<?php

namespace App\Models;

use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use App\Models\Concerns\IsMemberLoan;
use App\Models\Contracts\MemberLoan;
use App\Support\LoanTypes;
use Illuminate\Database\Eloquent\Model;

class CharacterLoan extends Model implements MemberLoan
{
    use IsMemberLoan;

    protected $fillable = [
        'member_id',
        'status',
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

    protected static function booted(): void
    {
        static::creating(function (CharacterLoan $loan): void {
            if (blank($loan->loan_type)) {
                $loan->loan_type = LoanTypes::CHARACTER;
            }
        });
    }

    public function printType(): string
    {
        return 'character';
    }
}
