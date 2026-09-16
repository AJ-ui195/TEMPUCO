<?php

namespace App\Filament\Auth\Pages;

use App\Support\PasswordRules;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangePassword extends Page
{
    protected static bool $isDiscovered = false;

    protected static ?string $slug = 'change-password';

    protected static ?string $title = 'Change password';

    protected static ?string $navigationLabel = 'Change password';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();

        return (bool) ($user?->must_change_password ?? false);
    }

    public static function isMultiFactorAuthenticationRequired(Panel $panel): bool
    {
        return false;
    }

    /**
     * @return array<string>
     */
    public static function getWithoutRouteMiddleware(Panel $panel): array
    {
        return [
            EnsureMultiFactorAuthenticationIsEnabled::class,
        ];
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Set a new password'))
                    ->description(__('You can choose a new password now, or skip and continue with the current one.'))
                    ->schema([
                        TextInput::make('current_password')
                            ->label(__('Current password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->currentPassword(),
                        TextInput::make('password')
                            ->label(__('New password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(PasswordRules::rule())
                            ->confirmed()
                            ->helperText(PasswordRules::helperText()),
                        TextInput::make('password_confirmation')
                            ->label(__('Confirm new password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label(__('Update password'))
                                ->submit('save'),
                            Action::make('skip')
                                ->label(__('Skip for now'))
                                ->color('gray')
                                ->action('skip'),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Filament::auth()->user();

        if (! $user) {
            abort(403);
        }

        if (! Hash::check((string) $data['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'data.current_password' => __('The password is incorrect.'),
            ]);
        }

        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
        ])->save();

        Notification::make()
            ->title(__('Password updated'))
            ->success()
            ->send();

        $this->redirect(Filament::getUrl());
    }

    public function skip(): void
    {
        $user = Filament::auth()->user();

        if (! $user) {
            abort(403);
        }

        $user->forceFill([
            'must_change_password' => false,
        ])->save();

        $this->redirect(Filament::getUrl());
    }
}
