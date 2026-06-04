<?php

namespace App\Models;

use App\Enums\LoanCategory;
use App\Support\AmountInWords;
use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
    protected $fillable = [
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function certification(): HasOne
    {
        return $this->hasOne(LoanCertification::class);
    }

    public function committeeDecision(): HasOne
    {
        return $this->hasOne(LoanCommitteeDecision::class);
    }

    public function amountInWords(): string
    {
        return AmountInWords::format($this->loan_amount);
    }
}
