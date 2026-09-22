<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\MemberEditPin;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class MemberEditPinPage extends Page
{
    protected static ?string $navigationLabel = 'Change PIN';

    protected static ?string $title = 'Change PIN';

    protected static ?string $slug = 'member-edit-pin';

    protected static ?int $navigationSort = 91;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected string $view = 'filament.admin.member-edit-pin';

    public string $currentPin = '';

    public string $newPin = '';

    public string $newPinConfirmation = '';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public function getTitle(): string|Htmlable
    {
        return __('Change PIN');
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function updatePin(): void
    {
        $this->validate([
            'currentPin' => ['required', 'string'],
            'newPin' => ['required', 'string', 'min:'.MemberEditPin::MIN_LENGTH],
            'newPinConfirmation' => ['required', 'string', 'same:newPin'],
        ], [
            'newPinConfirmation.same' => __('The PIN confirmation does not match.'),
        ], [
            'currentPin' => __('Current PIN'),
            'newPin' => __('New PIN'),
            'newPinConfirmation' => __('Confirm New PIN'),
        ]);

        try {
            MemberEditPin::update($this->currentPin, $this->newPin);
        } catch (InvalidArgumentException $exception) {
            $this->addError('currentPin', $exception->getMessage());

            return;
        }

        $this->reset('currentPin', 'newPin', 'newPinConfirmation');

        Notification::make()
            ->title(__('PIN updated'))
            ->body(__('The member-edit PIN was changed.'))
            ->success()
            ->send();
    }
}
