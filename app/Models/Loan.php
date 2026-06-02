<?php

namespace App\Models;

use App\Enums\LoanCategory;
use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'loan_category',
        'applicant_name',
        'applicant_address',
        'apply_loan',
        'loan_type',
        'loan_amount',
        'loan_amount_words',
        'loan_period_months',
        'installment_amount',
        'first_payment_due_date',
        'loan_purpose',
        'purpose_of_loan',
        'purpose_of_loan_other',
        'mode_of_payment',
        'applicant_signed_at',
        'applicant_signature_name',
        'loan_date',
        'approved_at',
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
            'cert_fixed_savings_deposits' => 'decimal:2',
            'cert_standing_loan' => 'decimal:2',
            'committee_approved_amount' => 'decimal:2',
            'first_payment_due_date' => 'date',
            'applicant_signed_at' => 'date',
            'cert_date_of_birth' => 'date',
            'cert_treasurer_signed_at' => 'date',
            'committee_meeting_date' => 'date',
            'committee_minutes_date' => 'date',
            'loan_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }
}
