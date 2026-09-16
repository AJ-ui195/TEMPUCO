<?php

namespace App\Filament\Auth\Pages;

use App\Enums\UserRole;
use App\Models\Member;
use App\Models\User;
use App\Support\AdminEmailMfa;
use App\Support\AuditLog;
use App\Support\RoleDashboard;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected const MAX_ATTEMPTS = 5;

    protected const LOCKOUT_SECONDS = 900;

    public int $throttleSecondsRemaining = 0;

    public function mount(): void
    {
        if (Filament::getCurrentPanel()?->getId() !== 'auth') {
            $this->redirect(RoleDashboard::loginUrl());

            return;
        }

        $account = RoleDashboard::currentUser();

        if ($account && ($url = RoleDashboard::url($account))) {
            $this->redirect($url);

            return;
        }

        $this->form->fill();
        $this->syncThrottleSecondsRemaining();
    }

    public function syncThrottleSecondsRemaining(): void
    {
        $wasThrottled = $this->throttleSecondsRemaining > 0;

        $key = $this->getLoginRateLimitKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $this->throttleSecondsRemaining = RateLimiter::availableIn($key);
        } else {
            $this->throttleSecondsRemaining = 0;
        }

        if (! $this->isLoginThrottled() && $wasThrottled) {
            $this->resetErrorBag('data.email');
        }
    }

    public function isLoginThrottled(): bool
    {
        return $this->throttleSecondsRemaining > 0;
    }

    public function updatedDataEmail(): void
    {
        $this->syncThrottleSecondsRemaining();
    }

    public function authenticate(): ?LoginResponse
    {
        $this->syncThrottleSecondsRemaining();
        $this->ensureIsNotRateLimited();

        $data = $this->form->getState();
        $credentials = $this->getCredentialsFromFormData($data);
        $account = $this->findAccountByEmail((string) ($credentials['email'] ?? ''));

        /** @var SessionGuard $authGuard */
        $authGuard = $account instanceof Member
            ? Auth::guard('member')
            : Auth::guard('web');

        if (
            (! $account)
            || (! Hash::check((string) ($credentials['password'] ?? ''), (string) $account->getAuthPassword()))
        ) {
            $this->userUndertakingMultiFactorAuthentication = null;

            $this->fireFailedEvent($authGuard, $account, $credentials);
            $this->throwFailureValidationException();
        }

        if (($account instanceof User || $account instanceof Member) && ! $account->isActive()) {
            $this->userUndertakingMultiFactorAuthentication = null;

            throw ValidationException::withMessages([
                'data.email' => __('This account has been disabled.'),
            ]);
        }

        if ($account instanceof Member && ! $account->hasVerifiedEmail()) {
            $this->userUndertakingMultiFactorAuthentication = null;

            throw ValidationException::withMessages([
                'data.email' => __('Confirm this email address before signing in. Check the inbox for the confirmation link.'),
            ]);
        }

        if (
            filled($this->userUndertakingMultiFactorAuthentication)
            && $account instanceof User
            && (decrypt($this->userUndertakingMultiFactorAuthentication) === $account->getAuthIdentifier())
        ) {
            if ($this->isMultiFactorChallengeRateLimited($account)) {
                return null;
            }

            $this->multiFactorChallengeForm->validate();
            AdminEmailMfa::remember($account);
        } elseif ($account instanceof User && ! AdminEmailMfa::isRemembered($account)) {
            foreach (Filament::getMultiFactorAuthenticationProviders() as $multiFactorAuthenticationProvider) {
                if (! $multiFactorAuthenticationProvider->isEnabled($account)) {
                    continue;
                }

                $this->userUndertakingMultiFactorAuthentication = encrypt($account->getAuthIdentifier());

                if ($multiFactorAuthenticationProvider instanceof HasBeforeChallengeHook) {
                    $multiFactorAuthenticationProvider->beforeChallenge($account);
                }

                break;
            }

            if (filled($this->userUndertakingMultiFactorAuthentication)) {
                $this->multiFactorChallengeForm->fill();

                return null;
            }
        }

        $panelId = RoleDashboard::panelId($account);

        if (
            $panelId === null
            || ! ($account instanceof FilamentUser)
            || ! $account->canAccessPanel(Filament::getPanel($panelId))
        ) {
            $this->fireFailedEvent($authGuard, $account, $credentials);
            $this->throwFailureValidationException();
        }

        Auth::guard('web')->logout();
        Auth::guard('member')->logout();

        $authGuard->login($account, (bool) ($data['remember'] ?? false));

        $this->clearLoginRateLimiter();

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent()
                    ->disabled(fn (): bool => $this->isLoginThrottled()),
                $this->getRememberFormComponent()
                    ->disabled(fn (): bool => $this->isLoginThrottled()),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        $component = parent::getFormContentComponent();

        $component->footer([
            Actions::make($this->getFormActions())
                ->alignment($this->getFormActionsAlignment())
                ->fullWidth($this->hasFullWidthFormActions())
                ->key('login-form-actions'),
        ]);

        return $component;
    }

    public function partiallyRenderSchemaComponent(string $componentKey): void
    {
        if (str_ends_with($componentKey, '.login-throttle-countdown')) {
            $wasThrottled = $this->isLoginThrottled();

            $this->syncThrottleSecondsRemaining();

            parent::partiallyRenderSchemaComponent($componentKey);

            if ($this->isLoginThrottled() || ($wasThrottled && ! $this->isLoginThrottled())) {
                parent::partiallyRenderSchemaComponent('content.form.login-form-actions');
            }

            return;
        }

        parent::partiallyRenderSchemaComponent($componentKey);
    }

    public function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->disabled(fn (): bool => $this->isLoginThrottled())
            ->tooltip(fn (): ?string => $this->isLoginThrottled()
                ? __('Please wait :seconds seconds before signing in.', [
                    'seconds' => $this->throttleSecondsRemaining,
                ])
                : null);
    }

    protected function findAccountByEmail(string $email): User|Member|null
    {
        if ($email === '') {
            return null;
        }

        $staff = User::query()
            ->where('email', $email)
            ->where('role', '!=', UserRole::User)
            ->first();

        if ($staff instanceof User) {
            return $staff;
        }

        $member = Member::query()->where('email', $email)->first();

        return $member instanceof Member ? $member : null;
    }

    protected function ensureIsNotRateLimited(): void
    {
        $key = $this->getLoginRateLimitKey();

        if (! RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return;
        }

        $this->throttleSecondsRemaining = RateLimiter::availableIn($key);

        $this->getRateLimitedNotification(new TooManyRequestsException(
            static::class,
            'authenticate',
            request()->ip(),
            $this->throttleSecondsRemaining,
        ))?->send();

        throw $this->makeThrottledValidationException();
    }

    protected function recordFailedLoginAttempt(): void
    {
        $email = strtolower((string) ($this->data['email'] ?? ''));

        if ($email !== '') {
            session(['login_rate_limit_email' => $email]);
        }

        RateLimiter::hit($this->getLoginRateLimitKey(), self::LOCKOUT_SECONDS);
        $this->syncThrottleSecondsRemaining();
    }

    protected function clearLoginRateLimiter(): void
    {
        RateLimiter::clear($this->getLoginRateLimitKey());
        session()->forget('login_rate_limit_email');
        $this->throttleSecondsRemaining = 0;
    }

    protected function getLoginRateLimitKey(): string
    {
        $email = strtolower((string) (
            $this->data['email']
            ?? session('login_rate_limit_email', '')
            ?? ''
        ));

        return 'login-attempts:unified:'.sha1($email.'|'.request()->ip());
    }

    protected function getThrottledValidationMessage(): string
    {
        return __('Too many login attempts. Please wait for the countdown. (:seconds seconds)', [
            'seconds' => $this->throttleSecondsRemaining,
        ]);
    }

    protected function throwFailureValidationException(): never
    {
        $this->recordFailedLoginAttempt();

        $key = $this->getLoginRateLimitKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $this->throttleSecondsRemaining = RateLimiter::availableIn($key);

            AuditLog::record('login.lockout', null, [
                'panel' => 'auth',
                'email' => strtolower((string) ($this->data['email'] ?? '')),
            ]);

            $this->getRateLimitedNotification(new TooManyRequestsException(
                static::class,
                'authenticate',
                request()->ip(),
                $this->throttleSecondsRemaining,
            ))?->send();

            throw $this->makeThrottledValidationException();
        }

        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->belowContent(function (): ?Text {
                if (! $this->isLoginThrottled()) {
                    return null;
                }

                return Text::make($this->getThrottledValidationMessage())
                    ->color('danger')
                    ->key('login-throttle-countdown')
                    ->poll(fn (): ?string => $this->isLoginThrottled() ? '1s' : null);
            });
    }

    protected function makeThrottledValidationException(): ValidationException
    {
        $this->syncThrottleSecondsRemaining();

        return ValidationException::withMessages([]);
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('Too many login attempts'))
            ->body(__('Please wait :seconds seconds before trying again.', [
                'seconds' => $exception->secondsUntilAvailable,
            ]))
            ->danger();
    }
}
