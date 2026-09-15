<?php

namespace App\Models\Concerns;

use App\Enums\LoanStatus;
use App\Models\CharacterLoan;
use App\Models\LoanCertification;
use App\Models\LoanCommitteeDecision;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\QuickLoan;
use App\Models\RegularLoan;
use App\Support\AmountInWords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;

trait IsMemberLoan
{
    private const LOAN_DATE_COLUMN = 'loan_date';

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function user(): BelongsTo
    {
        return $this->member();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class, $this->loanForeignKey())
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function certification(): HasOne
    {
        return $this->hasOne(LoanCertification::class, $this->loanForeignKey());
    }

    public function committeeDecision(): HasOne
    {
        return $this->hasOne(LoanCommitteeDecision::class, $this->loanForeignKey());
    }

    public function amountInWords(): string
    {
        return AmountInWords::format($this->loan_amount);
    }

    public function loanForeignKey(): string
    {
        return match (static::class) {
            CharacterLoan::class => 'character_loan_id',
            QuickLoan::class => 'quick_loan_id',
            RegularLoan::class => 'regular_loan_id',
            default => throw new InvalidArgumentException('Unknown member loan type.'),
        };
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, Member $user): Builder
    {
        return $query->where('member_id', $user->id);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrderedByLoanDate(Builder $query): Builder
    {
        return $query->orderByDesc($query->qualifyColumn(self::LOAN_DATE_COLUMN));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWhereLoanDate(Builder $query, \DateTimeInterface|string $date): Builder
    {
        return $query->whereDate($query->qualifyColumn(self::LOAN_DATE_COLUMN), $date);
    }

    public function initializeIsMemberLoan(): void
    {
        $this->mergeCasts([
            'email_verified_at' => 'datetime',
            'rejected_at' => 'datetime',
        ]);
        $this->makeHidden(['email_verification_token']);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function isAwaitingEmailConfirmation(): bool
    {
        return $this->status === LoanStatus::AwaitingVerification;
    }
}
