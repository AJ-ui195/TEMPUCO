<?php

namespace App\Filament\Resources\Members\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\Members\Schemas\MemberForm;
use App\Models\Member;
use App\Models\User;
use App\Support\MemberAccount;
use App\Support\MemberQrCode;
use App\Support\PasswordRules;
use App\Support\PrintMemberQrCode;
use App\Support\StaffAccount;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class ManageMembers extends ManageRecords
{
    protected static string $resource = MemberResource::class;

    protected static ?string $title = 'Members';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newRecord')
                ->label(__('New member'))
                ->icon(Heroicon::OutlinedPlus)
                ->color('primary')
                ->modalHeading(__('Create account'))
                ->modalDescription(__('Choose what you want to create.'))
                ->modalContent(fn (): View => view('filament.members.create-type-picker'))
                ->modalSubmitAction(false)
                ->modalCancelAction(false),
        ];
    }

    public function createMemberAction(): CreateAction
    {
        return CreateAction::make('createMember')
            ->modalHeading(__('Create Member'))
            ->createAnother(false)
            ->schema(MemberForm::components())
            ->using(fn (array $data): Member => MemberAccount::create($data))
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title(__('Confirmation email sent'))
                    ->body(__('The member account can be used only after they confirm the address in the Email field.')),
            )
            ->after(function (Member $record): void {
                if (! $record->hasVerifiedEmail()) {
                    return;
                }

                $this->replaceMountedAction('showAccountQr', [
                    'member' => $record->getKey(),
                ]);
            });
    }

    public function createAdminAction(): CreateAction
    {
        return CreateAction::make('createAdmin')
            ->modalHeading(__('Create Admin'))
            ->model(User::class)
            ->modelLabel(__('admin'))
            ->createAnother(false)
            ->successNotificationTitle(__('Admin user created'))
            ->schema([
                TextInput::make('name')
                    ->label(__('Full name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('Email address'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(User::class),
                Select::make('role')
                    ->label(__('Role'))
                    ->options([
                        UserRole::Admin->value => UserRole::Admin->getLabel(),
                        UserRole::CollectionCashier->value => UserRole::CollectionCashier->getLabel(),
                        UserRole::Cashier->value => UserRole::Cashier->getLabel(),
                        UserRole::CanteenCashier->value => UserRole::CanteenCashier->getLabel(),
                        UserRole::Inventory->value => UserRole::Inventory->getLabel(),
                    ])
                    ->required()
                    ->default(UserRole::Admin->value)
                    ->native(false),
                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(PasswordRules::rule())
                    ->confirmed()
                    ->helperText(PasswordRules::helperText()),
                TextInput::make('password_confirmation')
                    ->password()
                    ->revealable()
                    ->required()
                    ->dehydrated(false),
            ])
            ->using(fn (array $data): User => StaffAccount::create($data));
    }

    public function showAccountQrAction(): Action
    {
        return Action::make('showAccountQr')
            ->modalHeading(fn (array $arguments): string => __('QR code — :name', [
                'name' => Member::query()->findOrFail($arguments['member'])->name,
            ]))
            ->modalContent(function (array $arguments): View {
                $member = Member::query()->findOrFail($arguments['member']);

                return view(
                    'filament.pos.member-qr-modal',
                    [
                        'user' => $member,
                        'qrCodeDataUri' => MemberQrCode::dataUriFor($member, scale: 4),
                    ],
                );
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'))
            ->extraModalFooterActions(fn (array $arguments): array => [
                Action::make('printQrCode')
                    ->label(__('Print QR code'))
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (): string => PrintMemberQrCode::printUrl(
                        Member::query()->findOrFail($arguments['member']),
                    ))
                    ->openUrlInNewTab()
                    ->color('primary'),
            ]);
    }
}
