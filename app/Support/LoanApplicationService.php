<?php

namespace App\Support;

use App\Enums\LoanCategory;
use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Models\CharacterLoan;
use App\Models\Contracts\MemberLoan;
use App\Models\Member;
use App\Models\QuickLoan;
use App\Models\RegularLoan;
use App\Notifications\VerifyLoanApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LoanApplicationService
{
    public const MAX_SUBMISSIONS_PER_HOUR = 5;

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(Member $member, array $data): MemberLoan
    {
        $applicationType = (string) ($data['loan_application_type'] ?? 'regular');
        $model = match ($applicationType) {
            'quick' => QuickLoan::class,
            'character' => CharacterLoan::class,
            default => RegularLoan::class,
        };

        Gate::forUser($member)->authorize('create', $model);

        if (! $member->isActive()) {
            throw ValidationException::withMessages([
                'loan_amount' => __('This account cannot submit loan applications.'),
            ]);
        }

        $this->ensureWithinRateLimit($member);
        $this->ensureNoPendingApplication($member);

        $loanAmount = PesoInput::parse($data['loan_amount'] ?? 0);

        if ($loanAmount < 1) {
            throw ValidationException::withMessages([
                'loan_amount' => __('Enter a valid loan amount.'),
            ]);
        }

        if ($applicationType === 'quick' && $loanAmount > QuickLoanLedgerEntries::MAX_AMOUNT) {
            throw ValidationException::withMessages([
                'loan_amount' => __('Quick loans cannot exceed ₱2,000.'),
            ]);
        }

        $loanType = match ($applicationType) {
            'quick' => LoanTypes::QUICK,
            'character' => CharacterLoanRules::resolveStoredType(
                (string) ($data['character_loan_type'] ?? $data['loan_type'] ?? LoanTypes::CHARACTER),
                $data['character_variant'] ?? null,
            ),
            default => in_array((string) ($data['loan_type'] ?? ''), [
                LoanTypes::REGULAR,
                LoanTypes::COLLATERALIZED,
            ], true)
                ? (string) $data['loan_type']
                : LoanTypes::REGULAR,
        };

        $periodMonths = (int) ($data['loan_period_months'] ?? 0);
        $installmentAmount = PesoInput::parse($data['installment_amount'] ?? 0);
        $firstPaymentDueDate = $data['first_payment_due_date'] ?? null;
        $signedAt = $data['applicant_signed_at'] ?? now()->toDateString();

        if ($applicationType === 'quick') {
            $installmentAmount = QuickLoanLedgerEntries::totalPayable($loanAmount);
            $firstPaymentDueDate = Carbon::parse($signedAt)->addMonthNoOverflow()->toDateString();
            $periodMonths = 1;
        }

        if ($applicationType === 'regular') {
            if ($periodMonths < 1) {
                throw ValidationException::withMessages([
                    'loan_period_months' => __('Enter a valid repayment period.'),
                ]);
            }

            $schedule = LoanTypes::isCollateralized($loanType)
                ? RegularLoanSchedule::calculateCollateralized(
                    $loanAmount,
                    $periodMonths,
                    $member->isRetiree(),
                )
                : RegularLoanSchedule::calculate(
                    $loanAmount,
                    $periodMonths,
                    ApdsRules::isSecondApdsAccount($member),
                );
            $installmentAmount = $schedule->monthlyInstallment;
        }

        if ($applicationType === 'character') {
            if ($periodMonths < 1) {
                $periodMonths = CharacterLoanRules::defaultTermMonths($loanType);
            }

            $installmentAmount = CharacterLoanRules::periodInterest(
                $loanType,
                $loanAmount,
                $periodMonths,
                $member->isRetiree(),
            );
            $firstPaymentDueDate = Carbon::parse($signedAt)->addMonthsNoOverflow(3)->toDateString();
        }

        $loan = new $model;
        $attributes = [
            'member_id' => $member->id,
            'status' => LoanStatus::AwaitingVerification,
            'approved_at' => null,
            'rejected_at' => null,
            'decided_by' => null,
            'decision_notes' => null,
            'email_verified_at' => null,
            'loan_amount' => $loanAmount,
            'loan_period_months' => $periodMonths,
            'installment_amount' => $installmentAmount,
            'first_payment_due_date' => $firstPaymentDueDate,
            'purpose_of_loan' => $data['purpose_of_loan'] ?? null,
            'purpose_of_loan_other' => ($data['purpose_of_loan'] ?? null) === LoanPurpose::Others->value
                ? ($data['purpose_of_loan_other'] ?? null)
                : null,
            'mode_of_payment' => $data['mode_of_payment'] ?? null,
            'applicant_signed_at' => $signedAt,
            'loan_date' => $applicationType === 'character'
                ? $signedAt
                : ($data['loan_date'] ?? now()->toDateString()),
            'loan_type' => $loanType,
        ];

        if ($applicationType === 'regular') {
            $attributes['loan_category'] = $data['loan_category'] ?? LoanCategory::AdditionalNew;
        }

        $loan->forceFill($attributes)->save();

        RateLimiter::hit($this->rateLimitKey($member), 3600);

        $this->startVerification($member, $loan);

        AuditLog::record('loan.submitted', $loan, [
            'loan_type' => $loan->loan_type,
            'loan_amount' => $loan->loan_amount,
            'awaiting_email_confirmation' => true,
        ], $member);

        return $loan->refresh();
    }

    public function startVerification(Member $member, MemberLoan $loan): void
    {
        $plainToken = Str::random(64);

        $loan->forceFill([
            'status' => LoanStatus::AwaitingVerification,
            'email_verification_token' => hash('sha256', $plainToken),
            'email_verified_at' => null,
        ])->save();

        $this->sendVerificationEmail($member, $loan, $plainToken);
    }

    public function confirmFromEmail(MemberLoan $loan, string $token): MemberLoan
    {
        if ($loan->status === LoanStatus::Pending && $loan->email_verified_at !== null) {
            $this->assertTokenMatches($loan, $token, allowMissingToken: true);

            return $loan;
        }

        if ($loan->status !== LoanStatus::AwaitingVerification) {
            throw ValidationException::withMessages([
                'token' => __('This loan application cannot be confirmed.'),
            ]);
        }

        $this->assertTokenMatches($loan, $token);

        $loan->forceFill([
            'status' => LoanStatus::Pending,
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ])->save();

        AuditLog::record('loan.email_confirmed', $loan, [], $loan->member);

        return $loan->refresh();
    }

    public function resendConfirmation(Member $member, MemberLoan $loan): void
    {
        Gate::forUser($member)->authorize('view', $loan);

        if ((int) $loan->member_id !== (int) $member->id) {
            abort(403);
        }

        if ($loan->status !== LoanStatus::AwaitingVerification) {
            throw ValidationException::withMessages([
                'loan' => __('This application has already been confirmed.'),
            ]);
        }

        $key = 'loan-verify-resend:'.$member->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'loan' => __('Please wait before requesting another confirmation email.'),
            ]);
        }

        $plainToken = Str::random(64);
        $loan->forceFill([
            'email_verification_token' => hash('sha256', $plainToken),
        ])->save();

        $this->sendVerificationEmail($member, $loan, $plainToken);
        RateLimiter::hit($key, 3600);

        AuditLog::record('loan.confirmation_resent', $loan, [], $member);
    }

    public function resolve(string $type, int $id): MemberLoan
    {
        $model = MemberLoans::modelForPrintType($type);

        abort_unless($model !== null, 404);

        return $model::query()->findOrFail($id);
    }

    private function sendVerificationEmail(Member $member, MemberLoan $loan, string $plainToken): void
    {
        $url = URL::temporarySignedRoute(
            'loans.verify-email',
            now()->addHours(48),
            [
                'type' => $loan->printType(),
                'loan' => $loan->getKey(),
                'token' => $plainToken,
            ],
        );

        $member->notify(new VerifyLoanApplication($loan, $url));
    }

    private function ensureWithinRateLimit(Member $member): void
    {
        $key = $this->rateLimitKey($member);

        if (RateLimiter::tooManyAttempts($key, self::MAX_SUBMISSIONS_PER_HOUR)) {
            throw ValidationException::withMessages([
                'loan_amount' => __('Too many loan applications. Please wait before submitting again.'),
            ]);
        }
    }

    private function ensureNoPendingApplication(Member $member): void
    {
        $hasPending = $member->loans()->contains(function (MemberLoan $loan): bool {
            return in_array($loan->status, [
                LoanStatus::AwaitingVerification,
                LoanStatus::Pending,
            ], true);
        });

        if ($hasPending) {
            throw ValidationException::withMessages([
                'loan_amount' => __('You already have a loan application pending review.'),
            ]);
        }
    }

    private function assertTokenMatches(MemberLoan $loan, string $token, bool $allowMissingToken = false): void
    {
        $stored = (string) $loan->email_verification_token;

        if ($allowMissingToken && $stored === '') {
            return;
        }

        if ($token === '' || $stored === '' || ! hash_equals($stored, hash('sha256', $token))) {
            throw ValidationException::withMessages([
                'token' => __('This confirmation link is invalid or has expired.'),
            ]);
        }
    }

    private function rateLimitKey(Member $member): string
    {
        return 'loan-apply:'.$member->id;
    }
}
