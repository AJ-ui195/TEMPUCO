<?php

namespace App\Models;

use App\Enums\LoanCategory;
use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use App\Support\AmountInWords;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    private const LOAN_DATE_COLUMN = 'loan_date';

    protected $fillable = [
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
        'decision_notes',
    ];

    protected $hidden = [
        'email_verification_token',
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
            'rejected_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'user_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'user_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
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

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'subject');
    }

    public function amountInWords(): string
    {
        return AmountInWords::format($this->loan_amount);
    }

    public function isAwaitingEmailConfirmation(): bool
    {
        return $this->status === LoanStatus::AwaitingVerification;
    }

    public function scopeForUser(Builder $query, Member $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeOrderedByLoanDate(Builder $query): Builder
    {
        return $query->orderByDesc(self::LOAN_DATE_COLUMN);
    }

    /**
     * @param  Builder<Loan>  $query
     * @return Builder<Loan>
     */
    public function scopeWhereLoanDate(Builder $query, \DateTimeInterface|string $date): Builder
    {
        return $query->whereDate($query->qualifyColumn(self::LOAN_DATE_COLUMN), $date);
    }
}
