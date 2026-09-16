<?php

namespace App\Support;

use App\Models\Member;
use App\Models\User;
use App\Notifications\VerifyMemberAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MemberAccount
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(array $data): Member
    {
        return DB::transaction(function () use ($data): Member {
            $password = $data['password'] ?? null;
            if (! filled($password)) {
                throw ValidationException::withMessages([
                    'password' => __('Password is required.'),
                ]);
            }

            Validator::make(
                ['password' => $password],
                ['password' => ['required', 'string', PasswordRules::rule()]],
            )->validate();

            $skipVerification = (bool) ($data['email_verified'] ?? false);
            $plainToken = $skipVerification ? null : Str::random(64);

            $member = Member::query()->create([
                'created_by' => self::creator()?->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'email_verified_at' => $skipVerification ? now() : null,
                'password' => $password,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'sex' => $data['sex'] ?? null,
                'civil_status' => $data['civil_status'] ?? null,
                'address' => $data['address'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'employer_department' => $data['employer_department'] ?? null,
                'is_retiree' => (bool) ($data['is_retiree'] ?? false),
                'points' => (int) ($data['points'] ?? 0),
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                'must_change_password' => array_key_exists('must_change_password', $data)
                    ? (bool) $data['must_change_password']
                    : true,
            ]);

            if ($plainToken !== null) {
                $member->forceFill([
                    'email_verification_token' => hash('sha256', $plainToken),
                ])->save();

                self::sendVerificationEmail($member, $plainToken);
            }

            AuditLog::record('member.created', $member, [
                'email' => $member->email,
                'awaiting_email_confirmation' => $plainToken !== null,
            ]);

            return $member->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function update(Member $member, array $data): Member
    {
        return DB::transaction(function () use ($member, $data): Member {
            $wasActive = $member->isActive();
            $emailChanged = strtolower((string) $member->email) !== strtolower((string) $data['email']);

            $memberUpdates = [
                'name' => $data['name'],
                'email' => $data['email'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'sex' => $data['sex'] ?? null,
                'civil_status' => $data['civil_status'] ?? null,
                'address' => $data['address'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'employer_department' => $data['employer_department'] ?? null,
                'is_retiree' => (bool) ($data['is_retiree'] ?? false),
            ];

            if (array_key_exists('is_active', $data)) {
                $memberUpdates['is_active'] = (bool) $data['is_active'];
            }

            if (filled($data['password'] ?? null)) {
                Validator::make(
                    ['password' => $data['password']],
                    ['password' => ['required', 'string', PasswordRules::rule()]],
                )->validate();

                $memberUpdates['password'] = $data['password'];
                $memberUpdates['must_change_password'] = true;
            }

            $plainToken = null;

            if ($emailChanged) {
                $plainToken = Str::random(64);
                $memberUpdates['email_verified_at'] = null;
                $memberUpdates['email_verification_token'] = hash('sha256', $plainToken);
            }

            $member->forceFill($memberUpdates)->save();

            if ($plainToken !== null) {
                self::sendVerificationEmail($member->refresh(), $plainToken);
            }

            if ($wasActive && array_key_exists('is_active', $data) && ! $data['is_active']) {
                AuditLog::record('member.disabled', $member);
            }

            if (filled($data['password'] ?? null)) {
                AuditLog::record('password.reset_by_admin', $member);
            }

            if ($emailChanged) {
                AuditLog::record('member.email_changed', $member, [
                    'email' => $member->email,
                ]);
            }

            return $member->refresh();
        });
    }

    public static function confirmFromEmail(Member $member, string $token): Member
    {
        if ($member->hasVerifiedEmail()) {
            self::assertTokenMatches($member, $token, allowMissingToken: true);

            return $member;
        }

        self::assertTokenMatches($member, $token);

        $member->forceFill([
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ])->save();

        AuditLog::record('member.email_confirmed', $member, [], $member);

        return $member->refresh();
    }

    public static function resendConfirmation(Member $member): void
    {
        if ($member->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => __('This email address is already confirmed.'),
            ]);
        }

        $key = 'member-verify-resend:'.$member->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => __('Please wait before requesting another confirmation email.'),
            ]);
        }

        $plainToken = Str::random(64);
        $member->forceFill([
            'email_verification_token' => hash('sha256', $plainToken),
        ])->save();

        self::sendVerificationEmail($member, $plainToken);
        RateLimiter::hit($key, 3600);

        AuditLog::record('member.confirmation_resent', $member);
    }

    public static function delete(Member $member): void
    {
        $member->delete();
    }

    public static function creator(): ?User
    {
        $creator = auth()->user();

        return $creator instanceof User ? $creator : null;
    }

    public static function creatorName(): string
    {
        $creator = self::creator();

        if ($creator && filled($creator->name)) {
            return (string) $creator->name;
        }

        return 'System';
    }

    private static function sendVerificationEmail(Member $member, string $plainToken): void
    {
        $url = URL::temporarySignedRoute(
            'members.verify-email',
            now()->addHours(48),
            [
                'member' => $member->id,
                'token' => $plainToken,
            ],
        );

        $member->notify(new VerifyMemberAccount($member, $url));
    }

    private static function assertTokenMatches(Member $member, string $token, bool $allowMissingToken = false): void
    {
        $stored = (string) $member->email_verification_token;

        if ($allowMissingToken && $stored === '') {
            return;
        }

        if ($token === '' || $stored === '' || ! hash_equals($stored, hash('sha256', $token))) {
            throw ValidationException::withMessages([
                'token' => __('This confirmation link is invalid or has expired.'),
            ]);
        }
    }
}
