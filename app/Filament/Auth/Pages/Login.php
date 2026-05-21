<?php

namespace App\Filament\Auth\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected const MAX_ATTEMPTS = 5;

    protected const LOCKOUT_SECONDS = 50;

    public int $throttleSecondsRemaining = 0;

    public function mount(): void
    {
        parent::mount();

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

        /** @var SessionGuard $authGuard */
        $authGuard = Filament::auth();

        $authProvider = $authGuard->getProvider(); /** @phpstan-ignore-line */
        $credentials = $this->getCredentialsFromFormData($data);

        $user = $authProvider->retrieveByCredentials($credentials);

        if ((! $user) || (! $authProvider->validateCredentials($user, $credentials))) {
            $this->userUndertakingMultiFactorAuthentication = null;

            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

        if (
            filled($this->userUndertakingMultiFactorAuthentication) &&
            (decrypt($this->userUndertakingMultiFactorAuthentication) === $user->getAuthIdentifier())
        ) {
            if ($this->isMultiFactorChallengeRateLimited($user)) {
                return null;
            }

            $this->multiFactorChallengeForm->validate();
        } else {
            foreach (Filament::getMultiFactorAuthenticationProviders() as $multiFactorAuthenticationProvider) {
                if (! $multiFactorAuthenticationProvider->isEnabled($user)) {
                    continue;
                }

                $this->userUndertakingMultiFactorAuthentication = encrypt($user->getAuthIdentifier());

                if ($multiFactorAuthenticationProvider instanceof HasBeforeChallengeHook) {
                    $multiFactorAuthenticationProvider->beforeChallenge($user);
                }

                break;
            }

            if (filled($this->userUndertakingMultiFactorAuthentication)) {
                $this->multiFactorChallengeForm->fill();

                return null;
            }
        }

        if (! $authGuard->attemptWhen($credentials, function (Authenticatable $user): bool {
            if (! ($user instanceof FilamentUser)) {
                return true;
            }

            return $user->canAccessPanel(Filament::getCurrentOrDefaultPanel());
        }, $data['remember'] ?? false)) {
            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

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
            \Filament\Schemas\Components\Actions::make($this->getFormActions())
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
        $panelId = Filament::getCurrentPanel()?->getId() ?? 'default';
        $email = strtolower((string) ($this->data['email'] ?? ''));

        if ($email !== '') {
            session(['login_rate_limit_email_'.$panelId => $email]);
        }

        RateLimiter::hit($this->getLoginRateLimitKey(), self::LOCKOUT_SECONDS);
        $this->syncThrottleSecondsRemaining();
    }

    protected function clearLoginRateLimiter(): void
    {
        $panelId = Filament::getCurrentPanel()?->getId() ?? 'default';

        RateLimiter::clear($this->getLoginRateLimitKey());
        session()->forget('login_rate_limit_email_'.$panelId);
        $this->throttleSecondsRemaining = 0;
    }

    protected function getLoginRateLimitKey(): string
    {
        $panelId = Filament::getCurrentPanel()?->getId() ?? 'default';
        $email = strtolower((string) (
            $this->data['email']
            ?? session('login_rate_limit_email_'.$panelId, '')
            ?? ''
        ));

        return 'login-attempts:'.$panelId.':'.sha1($email.'|'.request()->ip());
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
