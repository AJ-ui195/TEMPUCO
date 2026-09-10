<?php

namespace App\Filament\User\Pages;

use App\Models\User;
use App\Support\MemberQrCode;
use App\Support\PrintMemberQrCode;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class MemberQrCodePage extends Page
{
    protected static ?string $navigationLabel = 'QR code';

    protected static ?string $title = 'QR code';

    protected static ?string $slug = 'qr-code';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('QR code');
    }

    public function content(Schema $schema): Schema
    {
        /** @var User $user */
        $user = auth()->user();

        return $schema
            ->components([
                TextEntry::make('qr_code_display')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.member-qr-code', [
                            'user' => $user,
                            'qrCodeDataUri' => MemberQrCode::dataUriFor($user, scale: 2),
                        ])->render()
                    ))
                    ->columnSpanFull(),
                Actions::make([
                    Action::make('printQrCode')
                        ->label(__('Print QR code'))
                        ->icon(Heroicon::OutlinedPrinter)
                        ->url(fn (): string => PrintMemberQrCode::printUrl($user))
                        ->openUrlInNewTab()
                        ->color('primary')
                        ->size('lg'),
                ])
                    ->alignment(Alignment::Center)
                    ->columnSpanFull(),
            ]);
    }
}
