<?php

namespace App\Filament\Pos\Resources\Members\Pages;

use App\Filament\Pos\Resources\Members\MemberResource;
use App\Models\Member;
use App\Support\MemberAccount;
use App\Support\MemberQrCode;
use App\Support\PrintMemberQrCode;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
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
            CreateAction::make()
                ->label(__('Add member'))
                ->modalHeading(__('Add member'))
                ->createAnother(false)
                ->successNotificationTitle(__('Member added'))
                ->using(fn (array $data): Member => MemberAccount::create($data))
                ->after(function (Member $record): void {
                    $this->replaceMountedAction('showMemberQr', [
                        'member' => $record->getKey(),
                    ]);
                }),
        ];
    }

    public function showMemberQrAction(): Action
    {
        return Action::make('showMemberQr')
            ->modalHeading(fn (array $arguments): string => __('Member QR code — :name', [
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
