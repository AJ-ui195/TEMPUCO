<?php

namespace App\Filament\User\Pages;

use App\Enums\PosSaleChannel;
use App\Models\User;
use App\Support\MemberPosCredit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

class Credits extends Page
{
    protected static ?string $navigationLabel = 'Credits';

    protected static ?string $title = 'Credits';

    protected static ?string $slug = 'credits';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Credits');
    }

    protected function memberCredit(): MemberPosCredit
    {
        /** @var User $user */
        $user = auth()->user();

        return new MemberPosCredit($user);
    }

    public function viewOutstandingBalancesAction(): Action
    {
        return Action::make('viewOutstandingBalances')
            ->label(__('View full breakdown'))
            ->icon(Heroicon::OutlinedBanknotes)
            ->modalHeading(__('Outstanding balance'))
            ->modalContent(fn (): View => view('filament.user.credits-outstanding-modal', [
                'user' => auth()->user(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'));
    }

    public function viewGroceryCreditDetailsAction(): Action
    {
        return $this->creditDetailsAction(
            name: 'viewGroceryCreditDetails',
            channel: PosSaleChannel::Grocery,
        );
    }

    public function viewCanteenCreditDetailsAction(): Action
    {
        return $this->creditDetailsAction(
            name: 'viewCanteenCreditDetails',
            channel: PosSaleChannel::Canteen,
        );
    }

    protected function creditDetailsAction(string $name, PosSaleChannel $channel): Action
    {
        return Action::make($name)
            ->modalHeading(__('Unpaid :channel items', ['channel' => $channel->getLabel()]))
            ->modalContent(fn (): View => view('filament.user.credits-unpaid-items', [
                'items' => $this->memberCredit()->unpaidLineItems($channel),
                'channelLabel' => $channel->getLabel(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'));
    }

    public function content(Schema $schema): Schema
    {
        /** @var User $user */
        $user = auth()->user();

        return $schema
            ->components([
                TextEntry::make('balances_summary')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.credits-summary', ['user' => $user])->render()
                    ))
                    ->columnSpanFull(),
            ]);
    }
}
