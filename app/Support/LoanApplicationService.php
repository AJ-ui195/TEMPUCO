<?php

namespace App\Support;

use App\Enums\LoanCategory;
use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use App\Models\Loan;
use App\Models\Member;
use App\Notifications\VerifyLoanApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LoanApplicationService
{
    public const QUICK_LOAN_MAX_AMOUNT = 2000.0;

    public const MAX_SUBMISSIONS_PER_HOUR = 5;

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(Member $member, array $data): Loan
    {
        Gate::forUser($member)->authorize('create', Loan::class);

        if (! $member->isActive()) {
            throw ValidationException::withMessages([
                'loan_amount' => __('This account cannot submit loan applications.'),
            ]);
        }

        $this->ensureWithinRateLimit($member);
        $this->ensureNoPendingApplication($member);

        $applicationType = (string) ($data['loan_application_type'] ?? 'regular');
        $isQuickLoan = $applicationType === 'quick';
        $isCharacterLoan = $applicationType === 'character';
        $isRegularLoan = $applicationType === 'regular';

        $loanAmount = (float) ($data['loan_amount'] ?? 0);

        if ($loanAmount < 1) {
            throw ValidationException::withMessages([
                'loan_amount' => __('Enter a valid loan amount.'),
            ]);
        }

        if ($isQuickLoan && $loanAmount > self::QUICK_LOAN_MAX_AMOUNT) {
            throw ValidationException::withMessages([
                'loan_amount' => __('Quick loans cannot exceed ₱2,000.'),
            ]);
        }

        if ($isRegularLoan) {
            $this->assertRegularLoanRules($member, $data, $loanAmount);
        }

        $this->syncMemberProfile($member, $data, $isRegularLoan, $isQuickLoan);

        $loanType = match ($applicationType) {
            'quick' => LoanTypes::QUICK,
            'character' => LoanTypes::CHARACTER,
            default => (string) ($data['loan_type'] ?? ''),
        };

        $installmentAmount = $data['installment_amount'] ?? 0;

        if ($isRegularLoan) {
            $schedule = RegularLoanSchedule::calculate(
                $loanAmount,
                (int) $data['loan_period_months'],
                ApdsRules::isSecondApdsAccount($member),
            );
            $installmentAmount = $schedule->monthlyInstallment;
        }

        $plainToken = Str::random(64);

        $loan = new Loan;
        $loan->forceFill([
            'user_id' => $member->id,
            'status' => LoanStatus::AwaitingVerification,
            'approved_at' => null,
            'rejected_at' => null,
            'decided_by' => null,
            'decision_notes' => null,
            'email_verification_token' => hash('sha256', $plainToken),
            'email_verified_at' => null,
            'loan_category' => ($isQuickLoan || $isCharacterLoan)
                ? LoanCategory::AdditionalNew
                : $data['loan_category'],
            'loan_type' => $loanType,
            'loan_amount' => $loanAmount,
            'loan_period_months' => $data['loan_period_months'],
            'installment_amount' => $installmentAmount,
            'first_payment_due_date' => $data['first_payment_due_date'] ?? null,
            'purpose_of_loan' => $isQuickLoan ? null : ($data['purpose_of_loan'] ?? null),
            'purpose_of_loan_other' => $isQuickLoan ? null : (
                ($data['purpose_of_loan'] ?? null) === LoanPurpose::Others->value
                    ? ($data['purpose_of_loan_other'] ?? null)
                    : null
            ),
            'application_notes' => $isQuickLoan ? $this->buildQuickLoanPurpose($data) : null,
            'mode_of_payment' => $data['mode_of_payment'],
            'applicant_signed_at' => $data['applicant_signed_at'] ?? now()->toDateString(),
            'loan_date' => now()->toDateString(),
        ])->save();

        RateLimiter::hit($this->rateLimitKey($member), 3600);

        $this->sendVerificationEmail($member, $loan, $plainToken);

        AuditLog::record('loan.submitted', $loan, [
            'loan_type' => $loan->loan_type,
            'loan_amount' => $loan->loan_amount,
            'awaiting_email_confirmation' => true,
        ], $member);

        return $loan->refresh();
    }

    public function confirmFromEmail(Loan $loan, string $token): Loan
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

    public function resendConfirmation(Member $member, Loan $loan): void
    {
        Gate::forUser($member)->authorize('view', $loan);

        if ((int) $loan->user_id !== (int) $member->id) {
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
        $hasPending = $member->loans()
            ->whereIn('status', [
                LoanStatus::AwaitingVerification,
                LoanStatus::Pending,
            ])
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'loan_amount' => __('You already have a loan application pending review.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertRegularLoanRules(Member $member, array $data, float $loanAmount): void
    {
        $dob = filled($data['date_of_birth'] ?? null)
            ? Carbon::parse($data['date_of_birth'])
            : $member->date_of_birth;

        $ageError = ApdsRules::ageRequirementError(
            $dob,
            (int) ($data['loan_period_months'] ?? 0),
        );

        if ($ageError !== null) {
            throw new LoanApplicationException(__('APDS age requirement'), $ageError);
        }

        $restructureError = ApdsRules::restructureAggregateError(
            $member,
            $loanAmount,
            ($data['loan_category'] ?? null) === LoanCategory::Restructure->value,
        );

        if ($restructureError !== null) {
            throw new LoanApplicationException(__('Maximum loanable amount'), $restructureError);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncMemberProfile(Member $member, array $data, bool $isRegularLoan, bool $isQuickLoan): void
    {
        $memberUpdates = array_filter([
            'name' => $data['applicant_name'] ?? null,
            'address' => $data['applicant_address'] ?? null,
        ], fn (mixed $value): bool => filled($value));

        if ($isRegularLoan && filled($data['date_of_birth'] ?? null)) {
            $memberUpdates['date_of_birth'] = $data['date_of_birth'];
        }

        if ($isQuickLoan) {
            $memberUpdates = array_merge($memberUpdates, array_filter([
                'email' => $data['quick_email'] ?? null,
                'date_of_birth' => $data['quick_date_of_birth'] ?? null,
                'sex' => $data['quick_sex'] ?? null,
                'civil_status' => $data['quick_civil_status'] ?? null,
                'contact_number' => $data['quick_contact_number'] ?? null,
                'occupation' => $data['quick_occupation'] ?? null,
                'employer_department' => $data['quick_employer_department'] ?? null,
            ], fn (mixed $value): bool => filled($value)));
        }

        if ($memberUpdates !== []) {
            $member->update($memberUpdates);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildQuickLoanPurpose(array $data): string
    {
        $purposeLabel = LoanPurpose::tryFrom((string) ($data['purpose_of_loan'] ?? ''))?->getLabel()
            ?? (string) ($data['purpose_of_loan'] ?? '');

        if (($data['purpose_of_loan'] ?? null) === LoanPurpose::Others->value && filled($data['purpose_of_loan_other'] ?? null)) {
            $purposeLabel .= ': '.$data['purpose_of_loan_other'];
        }

        $paymentLabel = ModeOfPayment::tryFrom((string) ($data['mode_of_payment'] ?? ''))?->getLabel()
            ?? (string) ($data['mode_of_payment'] ?? 'N/A');

        $details = [
            'Purpose of loan: '.$purposeLabel,
            'Mode of payment: '.$paymentLabel,
            '',
            'Quick Loan Applicant Details:',
            'Address: '.($data['applicant_address'] ?? 'N/A'),
            'Date of birth: '.($data['quick_date_of_birth'] ?? 'N/A'),
            'Age: '.($data['quick_age'] ?? 'N/A'),
            'Sex: '.ucfirst((string) ($data['quick_sex'] ?? 'N/A')),
            'Civil status: '.ucfirst((string) ($data['quick_civil_status'] ?? 'N/A')),
            'Contact number: '.($data['quick_contact_number'] ?? 'N/A'),
            'Email: '.($data['quick_email'] ?? 'N/A'),
            'Occupation / Position: '.($data['quick_occupation'] ?? 'N/A'),
            'Employer / Department: '.($data['quick_employer_department'] ?? 'N/A'),
        ];

        return implode(PHP_EOL, $details);
    }

    private function sendVerificationEmail(Member $member, Loan $loan, string $plainToken): void
    {
        $url = URL::temporarySignedRoute(
            'loans.verify-email',
            now()->addHours(48),
            [
                'loan' => $loan->id,
                'token' => $plainToken,
            ],
        );

        $member->notify(new VerifyLoanApplication($loan, $url));
    }

    private function assertTokenMatches(Loan $loan, string $token, bool $allowMissingToken = false): void
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
